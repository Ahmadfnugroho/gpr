// ===================================================================
// ✅ GOOGLE APPS SCRIPT - SINKRONISASI GOOGLE SHEET & DATABASE  
// ✅ FITUR UTAMA:
// - Status conversion "Active" ↔ "active", "Inactive" ↔ "blacklist"
// - Gender conversion "Laki Laki" ↔ "male", "Perempuan" ↔ "female" 
// - Header normalization otomatis (STATUS → Status)
// - last_updated tidak overwrite timestamp kolom A
// - Incremental sync hanya data yang berubah
// - Manual trigger untuk kontrol penuh
// ===================================================================

// ✅ HELPER FUNCTIONS
function convertGenderToSheet(genderValue) {
  if (genderValue === 'male') return 'Laki Laki';
  if (genderValue === 'female') return 'Perempuan';
  return genderValue || '';
}

function convertGenderToDb(genderValue) {
  if (!genderValue) return '';
  var gender = genderValue.toString().toLowerCase().trim();
  if (gender === 'laki laki' || gender === 'laki-laki') return 'male';
  if (gender === 'perempuan') return 'female';
  return genderValue;
}

// ✅ NORMALIZE HEADERS - ubah nama kolom yang tidak konsisten
function normalizeHeaders() {
  var sheet = SpreadsheetApp.openById("1jWFRwX4l6nSJ4N0H0iyiXanq_BFbI9dsRetdb3_I-A8").getSheetByName("User");
  var headers = sheet.getRange(1, 1, 1, sheet.getLastColumn()).getValues()[0];
  var changed = false;
  
  for (var i = 0; i < headers.length; i++) {
    // Ubah STATUS menjadi Status  
    if (headers[i] === "STATUS") {
      sheet.getRange(1, i + 1).setValue("Status");
      Logger.log("✅ Kolom STATUS berhasil diubah menjadi Status di kolom " + (i + 1));
      changed = true;
    }
    
    // Ubah kolom terakhir jika bukan last_updated
    if (i === headers.length - 1 && headers[i] !== "last_updated") {
      sheet.getRange(1, i + 1).setValue("last_updated");
      Logger.log("✅ Kolom terakhir (" + headers[i] + ") berhasil diubah menjadi last_updated di kolom " + (i + 1));
      changed = true;
    }
  }
  
  // Refresh headers setelah perubahan
  if (changed) {
    headers = sheet.getRange(1, 1, 1, sheet.getLastColumn()).getValues()[0];
  }
  
  return changed;
}

// ✅ MAPPING ROW DATA FROM API TO SHEET ORDER
function mapRowToSheetOrder(row, apiHeaders, sheetHeaders, preserveTimestamp = false, originalTimestamp = null) {
  return sheetHeaders.map(function(header, index) {
    // Kolom A (Timestamp) - preserve original value
    if (index === 0 && header === "Timestamp" && preserveTimestamp) {
      return originalTimestamp || "";
    }

    // Mapping dari API ke Sheet
    var apiFieldValue = "";
    switch (header) {
      case "Timestamp":
        return new Date().toISOString();
      
      case "Email Address":
        apiFieldValue = row[apiHeaders.indexOf("Email Address")];
        break;
        
      case "Nama Lengkap (Sesuai KTP)":
        apiFieldValue = row[apiHeaders.indexOf("Nama Lengkap (Sesuai KTP)")];
        break;
        
      case "Alamat Tinggal Sekarang (Ditulis Lengkap)":
        apiFieldValue = row[apiHeaders.indexOf("Alamat Tinggal Sekarang (Ditulis Lengkap)")];
        break;
        
      case "No. Hp1":
        apiFieldValue = row[apiHeaders.indexOf("No. Hp1")];
        break;
        
      case "No. Hp2":
        apiFieldValue = row[apiHeaders.indexOf("No. Hp2")];
        break;
        
      case "Pekerjaan":
        apiFieldValue = row[apiHeaders.indexOf("Pekerjaan")];
        break;
        
      case "Alamat Kantor":
        apiFieldValue = row[apiHeaders.indexOf("Alamat Kantor")];
        break;
        
      case "Nama akun Instagram penyewa":
        apiFieldValue = row[apiHeaders.indexOf("Nama akun Instagram penyewa")];
        break;
        
      case "Nama Kontak Emergency":
        apiFieldValue = row[apiHeaders.indexOf("Nama Kontak Emergency")];
        break;
        
      case "No. Hp Kontak Emergency":
        apiFieldValue = row[apiHeaders.indexOf("No. Hp Kontak Emergency")];
        break;
        
      case "Jenis Kelamin":
        // ✅ Convert dari database format ke sheet format
        var genderDb = row[apiHeaders.indexOf("Jenis Kelamin")];
        apiFieldValue = convertGenderToSheet(genderDb);
        break;
        
      case "Mengetahui Global Photo Rental dari":
        apiFieldValue = row[apiHeaders.indexOf("Mengetahui Global Photo Rental dari")];
        break;
        
      case "Status":
        // ✅ Flexible mapping untuk Status/STATUS
        var statusIndex = apiHeaders.indexOf("Status");
        if (statusIndex === -1) statusIndex = apiHeaders.indexOf("STATUS");
        if (statusIndex === -1) statusIndex = apiHeaders.indexOf("status");
        apiFieldValue = statusIndex !== -1 ? row[statusIndex] : "";
        break;
        
      case "last_updated":
        apiFieldValue = row[apiHeaders.indexOf("updated_at")];
        break;
        
      default:
        apiFieldValue = "";
    }
    
    return apiFieldValue || "";
  });
}

// ✅ SYNC INCREMENTAL - HANYA DATA YANG BERUBAH (SHEET → DATABASE)
function syncDataIncremental() {
  var sheet = SpreadsheetApp.openById("1jWFRwX4l6nSJ4N0H0iyiXanq_BFbI9dsRetdb3_I-A8").getSheetByName("User");
  
  // ✅ Normalize headers first
  normalizeHeaders();
  
  // Get last sync timestamp
  var lastSyncAt = PropertiesService.getScriptProperties().getProperty("sheet_to_db_last_sync") || "";
  var currentTime = new Date().toISOString();
  
  Logger.log("Last sync: " + lastSyncAt);
  
  var headers = sheet.getRange(1, 1, 1, sheet.getLastColumn()).getValues()[0];
  var lastUpdatedColIndex = headers.indexOf("last_updated");
  
  if (lastUpdatedColIndex === -1) {
    Logger.log("Kolom last_updated tidak ditemukan");
    return;
  }
  
  // Get all data
  var dataRange = sheet.getRange(2, 1, sheet.getLastRow() - 1, sheet.getLastColumn());
  var allData = dataRange.getValues();
  
  // Filter only changed data
  var changedRows = [];
  
  for (var i = 0; i < allData.length; i++) {
    var row = allData[i];
    var updatedAt = row[lastUpdatedColIndex];
    var email = row[headers.indexOf("Email Address")];
    
    // Skip empty emails or rows without updated_at
    if (!email || !updatedAt) {
      continue;
    }
    
    
    // ✅ Convert gender dari sheet ke database format sebelum sync
    var genderIndex = headers.indexOf("Jenis Kelamin");
    if (genderIndex !== -1 && row[genderIndex]) {
      row[genderIndex] = convertGenderToDb(row[genderIndex]);
    }
    
    // Check if row was updated after last sync
    if (!lastSyncAt || new Date(updatedAt) > new Date(lastSyncAt)) {
      changedRows.push(row);
    }
  }
  
  Logger.log("Found " + changedRows.length + " changed rows since last sync");
  
  if (changedRows.length === 0) {
    Logger.log("No changes to sync");
    return;
  }
  
  // Prepare data with header
  var dataToSend = [headers].concat(changedRows);
  
  var options = {
    "method": "post",
    "contentType": "application/json",
    "headers": {
      "x-api-key": "gbTnWu4oBizYlgeZ0OPJlbpnG11ARjsf"
    },
    "payload": JSON.stringify({"values": dataToSend}),
    "muteHttpExceptions": true
  };
  
  var url = "https://admin.globalphotorental.com/api/google-sheet-sync";
  
  try {
    var response = UrlFetchApp.fetch(url, options);
    
    if (response.getResponseCode() === 200) {
      Logger.log("✅ Sync berhasil: " + response.getContentText());
      PropertiesService.getScriptProperties().setProperty("sheet_to_db_last_sync", currentTime);
    } else {
      Logger.log("❌ Sync error " + response.getResponseCode() + ": " + response.getContentText());
    }
  } catch (error) {
    Logger.log("❌ Sync error: " + error.toString());
  }
}

// ✅ IMPORT INCREMENTAL - HANYA DATA YANG BERUBAH (DATABASE → SHEET)
function importDataFromDatabase() {
  var lastSyncAt = PropertiesService.getScriptProperties().getProperty("db_to_sheet_last_sync") || "";
  
  var url = "https://admin.globalphotorental.com/api/google-sheet-export?since=" + encodeURIComponent(lastSyncAt);
  
  var options = {
    "method": "get",
    "headers": {
      "x-api-key": "gbTnWu4oBizYlgeZ0OPJlbpnG11ARjsf"
    },
    "muteHttpExceptions": true
  };
  
  var response = UrlFetchApp.fetch(url, options);
  Logger.log("Response: " + response.getContentText());
  
  if (response.getResponseCode() !== 200) {
    Logger.log("❌ Error fetching data: " + response.getContentText());
    return;
  }
  
  var result = JSON.parse(response.getContentText());
  var data = result.values;
  if (!data || !Array.isArray(data) || data.length < 2) {
    Logger.log("Data kosong atau tidak valid");
    return;
  }
  
  var sheet = SpreadsheetApp.openById("1jWFRwX4l6nSJ4N0H0iyiXanq_BFbI9dsRetdb3_I-A8").getSheetByName("User");
  var apiHeaders = data[0];
  var sheetHeaders = sheet.getRange(1, 1, 1, sheet.getLastColumn()).getValues()[0];
  
  Logger.log("API Headers: " + apiHeaders.join(", "));
  Logger.log("Sheet Headers: " + sheetHeaders.join(", "));
  
  var rows = sheet.getRange(2, 1, sheet.getLastRow() - 1, sheet.getLastColumn()).getValues();
  
  for (var i = 1; i < data.length; i++) {
    var row = data[i];
    var email = row[apiHeaders.indexOf("Email Address")];
    
    if (!email) {
      Logger.log("Email tidak ditemukan di row " + i);
      continue;
    }
    
    var match = sheet.createTextFinder(email).findNext();
    var incomingTimeStr = row[apiHeaders.indexOf("updated_at")];
    var incomingTime = incomingTimeStr ? new Date(incomingTimeStr) : new Date();
    
    if (match) {
      var sheetRowIndex = match.getRow() - 2;
      var sheetUpdatedAtStr = rows[sheetRowIndex][sheetHeaders.indexOf("last_updated")];
      var sheetTime = sheetUpdatedAtStr ? new Date(sheetUpdatedAtStr) : new Date(0);
      
      Logger.log("Comparing times for " + email + ": DB=" + incomingTime + ", Sheet=" + sheetTime);
      
      if (incomingTime > sheetTime) {
        var oldRow = rows[sheetRowIndex];
        
        // ✅ PRESERVE KOLOM A (Timestamp) - ambil nilai lama
        var originalTimestamp = oldRow[0];
        var mappedRow = mapRowToSheetOrder(row, apiHeaders, sheetHeaders, true, originalTimestamp);
        
        var changedColumns = [];
        for (var j = 1; j < sheetHeaders.length; j++) { // Skip kolom A
          if (String(oldRow[j]) !== String(mappedRow[j])) {
            changedColumns.push(sheetHeaders[j]);
          }
        }
        
        if (changedColumns.length > 0) {
          Logger.log("✅ Update untuk email: " + email + ", kolom berubah: " + changedColumns.join(", "));
          
          // ✅ UPDATE MULAI DARI KOLOM B - skip kolom A
          sheet.getRange(match.getRow(), 2, 1, mappedRow.length - 1)
               .setValues([mappedRow.slice(1)]);
               
          // ✅ JANGAN UPDATE last_updated karena ini dari database
        } else {
          Logger.log("Tidak ada perubahan data untuk email: " + email);
        }
      } else {
        Logger.log("Data di Sheet lebih baru, skip update untuk email: " + email);
      }
    } else {
      // ✅ DATA BARU
      var mappedRow = mapRowToSheetOrder(row, apiHeaders, sheetHeaders, true, new Date().toISOString());
      Logger.log("✅ Menambahkan data baru untuk email: " + email);
      sheet.appendRow(mappedRow);
    }
  }
  
  PropertiesService.getScriptProperties().setProperty("db_to_sheet_last_sync", new Date().toISOString());
}

// ✅ WEBHOOK untuk menerima data dari backend
function doPost(e) {
  try {
    const sheet = SpreadsheetApp.openById("1jWFRwX4l6nSJ4N0H0iyiXanq_BFbI9dsRetdb3_I-A8").getSheetByName("User");
    const data = JSON.parse(e.postData.contents);
    
    const headers = sheet.getRange(1, 1, 1, sheet.getLastColumn()).getValues()[0];
    const emailIndex = headers.indexOf("Email Address");
    
    if (emailIndex === -1) {
      return ContentService.createTextOutput("Header 'Email Address' tidak ditemukan").setMimeType(ContentService.MimeType.TEXT);
    }
    
    const rows = sheet.getRange(2, 1, sheet.getLastRow() - 1, sheet.getLastColumn()).getValues();
    let found = false;
    
    for (let i = 0; i < rows.length; i++) {
      if (rows[i][emailIndex] === data.email) {
        found = true;
        
        // Cek konflik updated_at
        const sheetUpdatedAtIndex = headers.indexOf("last_updated");
        if (sheetUpdatedAtIndex !== -1 && rows[i][sheetUpdatedAtIndex]) {
          const sheetTime = new Date(rows[i][sheetUpdatedAtIndex]);
          const incomingTime = new Date(data.updated_at);
          
          if (sheetTime > incomingTime) {
            return ContentService.createTextOutput("Data di sheet lebih baru, abaikan update").setMimeType(ContentService.MimeType.TEXT);
          }
        }
        
        // ✅ UPDATE DATA (skip kolom A - Timestamp)
        for (let j = 1; j < headers.length; j++) {
          switch (headers[j]) {
            case "Email Address": rows[i][j] = data.email || ""; break;
            case "Nama Lengkap (Sesuai KTP)": rows[i][j] = data.name || ""; break;
            case "Alamat Tinggal Sekarang (Ditulis Lengkap)": rows[i][j] = data.address || ""; break;
            case "No. Hp1": rows[i][j] = (data.phone_numbers && data.phone_numbers[0]) || ""; break;
            case "No. Hp2": rows[i][j] = (data.phone_numbers && data.phone_numbers[1]) || ""; break;
            case "Pekerjaan": rows[i][j] = data.job || ""; break;
            case "Alamat Kantor": rows[i][j] = data.office_address || ""; break;
            case "Nama akun Instagram penyewa": rows[i][j] = data.instagram_username || ""; break;
            case "Nama Kontak Emergency": rows[i][j] = data.emergency_contact_name || ""; break;
            case "No. Hp Kontak Emergency": rows[i][j] = data.emergency_contact_number || ""; break;
            case "Jenis Kelamin": rows[i][j] = convertGenderToSheet(data.gender); break;
            case "Mengetahui Global Photo Rental dari": rows[i][j] = data.source_info || ""; break;
            case "Status": rows[i][j] = data.status || ""; break;
            case "last_updated": rows[i][j] = data.updated_at || ""; break;
          }
        }
        
        sheet.getRange(i + 2, 2, 1, headers.length - 1).setValues([rows[i].slice(1)]);
        break;
      }
    }
    
    if (!found) {
      const newRow = headers.map((header, index) => {
        if (index === 0 && header === "Timestamp") {
          return new Date().toISOString();
        }
        
        switch (header) {
          case "Email Address": return data.email || "";
          case "Nama Lengkap (Sesuai KTP)": return data.name || "";
          case "Alamat Tinggal Sekarang (Ditulis Lengkap)": return data.address || "";
          case "No. Hp1": return (data.phone_numbers && data.phone_numbers[0]) || "";
          case "No. Hp2": return (data.phone_numbers && data.phone_numbers[1]) || "";
          case "Pekerjaan": return data.job || "";
          case "Alamat Kantor": return data.office_address || "";
          case "Nama akun Instagram penyewa": return data.instagram_username || "";
          case "Nama Kontak Emergency": return data.emergency_contact_name || "";
          case "No. Hp Kontak Emergency": return data.emergency_contact_number || "";
          case "Jenis Kelamin": return convertGenderToSheet(data.gender);
          case "Mengetahui Global Photo Rental dari": return data.source_info || "";
          case "Status": return data.status || "";
          case "last_updated": return data.updated_at || "";
          default: return "";
        }
      });
      
      sheet.appendRow(newRow);
    }
    
    return ContentService.createTextOutput("OK").setMimeType(ContentService.MimeType.TEXT);
  } catch (err) {
    return ContentService.createTextOutput("Error: " + err.message).setMimeType(ContentService.MimeType.TEXT);
  }
}

// ✅ DEBUG FUNCTION - Test Status Column Sync
function debugStatusSync() {
  var sheet = SpreadsheetApp.openById("1jWFRwX4l6nSJ4N0H0iyiXanq_BFbI9dsRetdb3_I-A8").getSheetByName("User");
  
  Logger.log("💬 DEBUG: Testing Status column sync...");
  
  var headers = sheet.getRange(1, 1, 1, sheet.getLastColumn()).getValues()[0];
  Logger.log("📊 Headers: " + headers.join(", "));
  
  var statusIndex = headers.indexOf("Status");
  var statusIndexAlt = headers.indexOf("STATUS");
  
  Logger.log("📊 Status column index: " + statusIndex);
  Logger.log("📊 STATUS column index: " + statusIndexAlt);
  
  if (statusIndex === -1 && statusIndexAlt === -1) {
    Logger.log("❌ Status column NOT FOUND!");
    return;
  }
  
  // Get sample data
  var dataRange = sheet.getRange(2, 1, Math.min(3, sheet.getLastRow() - 1), sheet.getLastColumn());
  var sampleData = dataRange.getValues();
  
  for (var i = 0; i < sampleData.length; i++) {
    var row = sampleData[i];
    var email = row[headers.indexOf("Email Address")];
    var statusVal = statusIndex !== -1 ? row[statusIndex] : (statusIndexAlt !== -1 ? row[statusIndexAlt] : "N/A");
    
    Logger.log("📊 Row " + (i+2) + " - Email: " + email + ", Status: '" + statusVal + "'");
  }
}

// ✅ MANUAL TEST - Force sync single row to test Status
function testStatusSyncSingleRow() {
  var sheet = SpreadsheetApp.openById("1jWFRwX4l6nSJ4N0H0iyiXanq_BFbI9dsRetdb3_I-A8").getSheetByName("User");
  
  Logger.log("🧪 MANUAL TEST: Force Status sync for first row...");
  
  var headers = sheet.getRange(1, 1, 1, sheet.getLastColumn()).getValues()[0];
  var firstRow = sheet.getRange(2, 1, 1, sheet.getLastColumn()).getValues()[0];
  
  var email = firstRow[headers.indexOf("Email Address")];
  var statusIndex = headers.indexOf("Status");
  var statusValue = statusIndex !== -1 ? firstRow[statusIndex] : "N/A";
  
  Logger.log("📊 Test email: " + email);
  Logger.log("📊 Test status: '" + statusValue + "' (column " + statusIndex + ")");
  
  // Force update last_updated to make it sync
  var lastUpdatedColIndex = headers.indexOf("last_updated") + 1;
  sheet.getRange(2, lastUpdatedColIndex).setValue(new Date().toISOString());
  
  Logger.log("📊 Updated last_updated timestamp for testing");
  
  // Prepare data to send (just this one row)
  var dataToSend = [headers, firstRow];
  
  var options = {
    "method": "post",
    "contentType": "application/json",
    "headers": {
      "x-api-key": "gbTnWu4oBizYlgeZ0OPJlbpnG11ARjsf"
    },
    "payload": JSON.stringify({"values": dataToSend}),
    "muteHttpExceptions": true
  };
  
  var url = "https://admin.globalphotorental.com/api/google-sheet-sync";
  
  try {
    var response = UrlFetchApp.fetch(url, options);
    
    Logger.log("📊 Response Code: " + response.getResponseCode());
    Logger.log("📊 Response Body: " + response.getContentText());
    
    if (response.getResponseCode() === 200) {
      Logger.log("✅ Test sync berhasil!");
    } else {
      Logger.log("❌ Test sync gagal!");
    }
  } catch (error) {
    Logger.log("❌ Test sync error: " + error.toString());
  }
}

// ✅ AUTO-UPDATE last_updated saat ada edit (tapi JANGAN update kolom A dan last_updated)
function onEdit(e) {
  var sheet = e.source.getSheetByName("User");
  if (!sheet || sheet.getName() !== "User") return;
  
  var editedRange = e.range;
  var headers = sheet.getRange(1, 1, 1, sheet.getLastColumn()).getValues()[0];
  var lastUpdatedColIndex = headers.indexOf("last_updated") + 1;
  
  if (lastUpdatedColIndex === 0) return;
  
  // ✅ DEBUG: Log edit details
  var statusIndex = headers.indexOf("Status") + 1;
  if (editedRange.getColumn() === statusIndex) {
    var newValue = editedRange.getValue();
    Logger.log("📊 Status edited to: '" + newValue + "' at row " + editedRange.getRow());
  }
  
  // ✅ CEGAH update last_updated jika yang diedit adalah:
  // - Kolom A (Timestamp)
  // - Kolom last_updated itu sendiri
  if (editedRange.getColumn() === 1 || editedRange.getColumn() === lastUpdatedColIndex) {
    return;
  }
  
  // Update last_updated
  sheet.getRange(editedRange.getRow(), lastUpdatedColIndex).setValue(new Date().toISOString());
}
