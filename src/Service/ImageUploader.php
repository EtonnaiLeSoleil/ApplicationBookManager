<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class ImageUploader
{
    public function __construct(
        private string $uploadDir,
        private string $publicPath,
        private SluggerInterface $slugger
    ) {}

    /**
     * Upload une image et retourne son chemin relatif (ex: uploads/covers/mon-livre-123456.jpg)
     */
    public function upload(UploadedFile $file, string $slug): string
    {
        // Génère un nom de fichier sûr
        $extension = $file->guessExtension() ?? 'bin';
        $filename = $this->slugger->slug($slug)->lower() . '-' . time() . '.' . $extension;

        // Déplace le fichier dans le dossier public/uploads/covers
        $file->move($this->uploadDir, $filename);

        // Retourne le chemin relatif qui sera stocké en BDD
        return $this->publicPath . '/' . $filename;
    }

    /**
     * Supprime l’ancien fichier si présent
     */
    public function deleteIfExists(?string $relativePath): void
    {
        if (!$relativePath) {
            return;
        }

        $fullPath = $this->uploadDir . '/' . basename($relativePath);

        if (file_exists($fullPath)) {
            @unlink($fullPath);
        }
    }
}
