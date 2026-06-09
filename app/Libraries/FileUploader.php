<?php

namespace App\Libraries;

use App\Models\PostFileModel;
use CodeIgniter\HTTP\Files\UploadedFile;

class FileUploader
{
    // 허용 이미지 확장자
    private const IMAGE_EXTS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    // 허용 파일 확장자 (보안: 실행파일 차단)
    private const ALLOWED_EXTS = [
        'jpg', 'jpeg', 'png', 'gif', 'webp',
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
        'zip', 'txt', 'hwp',
    ];
    private const MAX_SIZE = 10 * 1024 * 1024; // 10MB

    private PostFileModel $fileModel;

    public function __construct()
    {
        $this->fileModel = new PostFileModel();
    }

    /**
     * 게시글의 첨부파일 일괄 저장
     * @return array ['saved' => int, 'errors' => array]
     */
    public function savePostFiles(int $postId, array $uploadedFiles): array
    {
        $saved  = 0;
        $errors = [];

        foreach ($uploadedFiles as $file) {
            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                continue;
            }

            $result = $this->saveFile($postId, $file);
            if ($result['success']) {
                $saved++;
            } else {
                $errors[] = $result['error'];
            }
        }

        return ['saved' => $saved, 'errors' => $errors];
    }

    private function saveFile(int $postId, UploadedFile $file): array
    {
        $ext = strtolower($file->getClientExtension());

        if (! in_array($ext, self::ALLOWED_EXTS)) {
            return ['success' => false, 'error' => "{$file->getName()}: 허용되지 않는 파일 형식"];
        }

        if ($file->getSize() > self::MAX_SIZE) {
            return ['success' => false, 'error' => "{$file->getName()}: 파일 크기 초과 (최대 10MB)"];
        }

        $isImage    = in_array($ext, self::IMAGE_EXTS);
        $subDir     = $isImage ? 'images' : 'files';
        $uploadPath = FCPATH . "uploads/board/{$subDir}/" . date('Y/m');

        if (! is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        $storedName = bin2hex(random_bytes(16)) . '.' . $ext;
        $file->move($uploadPath, $storedName);

        $relativePath = "uploads/board/{$subDir}/" . date('Y/m') . "/{$storedName}";

        $this->fileModel->insert([
            'post_id'       => $postId,
            'original_name' => $file->getName(),
            'stored_name'   => $storedName,
            'file_path'     => $relativePath,
            'file_size'     => $file->getSize(),
            'mime_type'     => $file->getClientMimeType(),
            'is_image'      => $isImage ? 1 : 0,
        ]);

        return ['success' => true];
    }

    public function deleteFile(array $fileRecord): void
    {
        $fullPath = FCPATH . $fileRecord['file_path'];
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
        $this->fileModel->delete($fileRecord['id']);
    }
}
