<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Rule;

class FileUpload extends Component
{
    use WithFileUploads;

    #[Rule('required|file|max:10240')] // Sesuai config 10MB
    public $photo;

    public function save()
    {
        $this->validate();

        $path = $this->photo->store('uploads', 'public');

        session()->flash('message', 'File berhasil diunggah ke: ' . $path);
        
        $this->reset('photo');
    }

    public function render()
    {
        return view('livewire.file-upload');
    }
}
