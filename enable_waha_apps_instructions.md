# Enable WAHA Apps Dashboard

## Method 1: SSH Access to Server

1. **SSH to your server:**
   ```bash
   ssh your_user@whatsapp.globalphotorental.com
   ```

2. **Find the WAHA Docker container:**
   ```bash
   docker ps | grep waha
   # or
   docker ps -a
   ```

3. **Stop the current container:**
   ```bash
   docker stop [container_name_or_id]
   ```

4. **Run with WAHA_APPS_ENABLED=true:**
   ```bash
   docker run -d \
     --name waha \
     -p 3000:3000 \
     -e WAHA_APPS_ENABLED=true \
     -e WHATSAPP_API_KEY=gbTnWu4oBizYlgeZ0OPJlbpnG11ARjsf \
     devlikeapro/waha
   ```

## Method 2: Using Docker Compose

If using docker-compose, edit your `docker-compose.yml`:

```yaml
version: '3.8'
services:
  waha:
    image: devlikeapro/waha
    ports:
      - "3000:3000"
    environment:
      - WAHA_APPS_ENABLED=true
      - WHATSAPP_API_KEY=gbTnWu4oBizYlgeZ0OPJlbpnG11ARjsf
    restart: unless-stopped
```

Then restart:
```bash
docker-compose down
docker-compose up -d
```

## Method 3: Update existing container environment (if possible)

```bash
# Stop container
docker stop [container_name]

# Remove container (data will be preserved if using volumes)
docker rm [container_name]

# Run new container with updated environment
docker run -d \
  --name waha \
  -p 3000:3000 \
  -e WAHA_APPS_ENABLED=true \
  -e WHATSAPP_API_KEY=gbTnWu4oBizYlgeZ0OPJlbpnG11ARjsf \
  devlikeapro/waha
```

## Verify Apps are enabled

After restarting, check:
1. Visit: https://whatsapp.globalphotorental.com/dashboard
2. You should be able to access the apps section without the error

## Alternative: Use our Laravel Dashboard

If you can't access the server, I've already created a WhatsApp management dashboard in Laravel that provides similar functionality.
