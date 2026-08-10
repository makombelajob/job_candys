<?php

namespace App\Service;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class FileUploader
{
    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly string $uploadDirectory,
        private readonly string $tempDirectory,
    ) {
        $this->filesystem->mkdir($this->uploadDirectory);
        $this->filesystem->mkdir($this->tempDirectory);
    }

    public function upload(UploadedFile $file): string
    {
        if (!$file->isValid()) {
            throw new FileException(
                'Le fichier envoyé est invalide.'
            );
        }

        $extension = $file->guessExtension();

        if ($extension === null) {
            throw new FileException(
                'Impossible de déterminer le type du fichier.'
            );
        }

        $temporaryName = bin2hex(random_bytes(8)) . '.' . $extension;

        try {
            $file->move(
                $this->tempDirectory,
                $temporaryName
            );
        } catch (\Throwable $e) {
            throw new FileException(
                'Impossible de déplacer le fichier temporairement.',
                0,
                $e
            );
        }

        $temporaryPath = $this->tempDirectory . '/' . $temporaryName;

        if (!$this->filesystem->exists($temporaryPath)) {
            throw new FileException(
                'Le fichier temporaire n\'existe pas.'
            );
        }

        $finalName = bin2hex(random_bytes(8)) . '.' . $extension;
        $finalPath = $this->uploadDirectory . '/' . $finalName;

        try {
            $this->filesystem->rename($temporaryPath, $finalPath);
        } catch (\Throwable $e) {
            if ($this->filesystem->exists($temporaryPath)) {
                $this->filesystem->remove($temporaryPath);
            }

            throw new FileException(
                'Impossible de déplacer le fichier définitivement.',
                0,
                $e
            );
        }

        if (!$this->filesystem->exists($finalPath)) {
            throw new FileException(
                'Le fichier final n\'existe pas après son déplacement.'
            );
        }

        return $finalName;
    }

    public function getPath(string $filename): string
    {
        return $this->uploadDirectory . '/' . basename($filename);
    }

    public function remove(string $filename): void
    {
        $path = $this->uploadDirectory . '/' . basename($filename);

        if ($this->filesystem->exists($path)) {
            $this->filesystem->remove($path);
        }
    }

    public function exists(string $filename): bool
    {
        $path = $this->uploadDirectory . '/' . basename($filename);

        return $this->filesystem->exists($path);
    }
}
