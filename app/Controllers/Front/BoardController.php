<?php

namespace App\Controllers\Front;

use App\Controllers\BaseController;
use App\Libraries\FileUploader;
use App\Models\BoardModel;
use App\Models\PostCommentModel;
use App\Models\PostFileModel;
use App\Models\PostModel;

class BoardController extends BaseController
{
    private BoardModel      $boardModel;
    private PostModel       $postModel;
    private PostFileModel   $fileModel;
    private PostCommentModel $commentModel;
    private FileUploader    $uploader;

    public function __construct()
    {
        $this->boardModel   = new BoardModel();
        $this->postModel    = new PostModel();
        $this->fileModel    = new PostFileModel();
        $this->commentModel = new PostCommentModel();
        $this->uploader     = new FileUploader();
    }

    // ─── 목록 ───────────────────────────────────────────────────────────────

    public function index(string $boardSlug)
    {
        $board = $this->boardModel->getBySlug($boardSlug);
        if (! $board) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        if (! $this->checkPermission($board['read_permission'])) {
            return redirect()->to('/auth/login')->with('error', '로그인이 필요합니다.');
        }

        $page    = (int) ($this->request->getGet('page') ?? 1);
        $keyword = $this->request->getGet('keyword');
        $type    = $this->request->getGet('type') ?? 'title';

        if ($keyword) {
            $result = $this->postModel->search($board['id'], $keyword, $type, $page, $board['posts_per_page']);
            $posts   = $result['posts'];
            $total   = $result['total'];
            $notices = [];
        } else {
            $list    = $this->postModel->getList($board['id'], $page, $board['posts_per_page']);
            $posts   = $list['posts'];
            $notices = $list['notices'];
            $total   = $this->postModel->getTotalCount($board['id']);
        }

        $totalPages = (int) ceil($total / $board['posts_per_page']);

        return $this->render('board/list', [
            'board'       => $board,
            'posts'       => $posts,
            'notices'     => $notices,
            'currentPage' => $page,
            'totalPages'  => $totalPages,
            'total'       => $total,
            'keyword'     => $keyword,
            'searchType'  => $type,
        ]);
    }

    // ─── 상세 ───────────────────────────────────────────────────────────────

    public function view(string $boardSlug, int $postId)
    {
        $board = $this->boardModel->getBySlug($boardSlug);
        $post  = $this->postModel->getDetail($postId);

        if (! $board || ! $post || $post['board_id'] != $board['id']) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        if (! $this->checkPermission($board['read_permission'])) {
            return redirect()->to('/auth/login')->with('error', '로그인이 필요합니다.');
        }

        // 비밀글 처리
        if ($post['is_secret'] && ! $this->canAccessSecret($post)) {
            return redirect()->back()->with('error', '비밀글입니다.');
        }

        $this->postModel->incrementView($postId);

        $files    = $this->fileModel->getByPost($postId);
        $comments = $this->commentModel->getByPost($postId);

        return $this->render('board/view', compact('board', 'post', 'files', 'comments'));
    }

    // ─── 작성 ───────────────────────────────────────────────────────────────

    public function write(string $boardSlug)
    {
        $board = $this->boardModel->getBySlug($boardSlug);
        if (! $board) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        if (! $this->checkPermission($board['write_permission'])) {
            return redirect()->to('/auth/login')
                             ->with('error', '글쓰기 권한이 없습니다.')
                             ->with('redirect_url', current_url());
        }

        return $this->render('board/write', ['board' => $board, 'post' => null]);
    }

    public function store(string $boardSlug)
    {
        $board = $this->boardModel->getBySlug($boardSlug);
        if (! $board || ! $this->checkPermission($board['write_permission'])) {
            return redirect()->back()->with('error', '권한이 없습니다.');
        }

        $isGuest = ! session()->get('user_id');

        $rules = ['title' => 'required|max_length[255]', 'content' => 'required'];
        if ($isGuest) {
            $rules['author_name']     = 'required|max_length[50]';
            $rules['author_password'] = 'required|min_length[4]';
        }

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $data = [
            'board_id'   => $board['id'],
            'user_id'    => session()->get('user_id'),
            'title'      => $this->request->getPost('title'),
            'content'    => $this->request->getPost('content'),
            'author_name'=> $isGuest
                            ? $this->request->getPost('author_name')
                            : session()->get('user_nickname'),
            'is_notice'  => $this->getUserRole() === 'admin' ? (int) $this->request->getPost('is_notice') : 0,
            'is_secret'  => (int) $this->request->getPost('is_secret'),
            'ip_address' => $this->request->getIPAddress(),
        ];

        if ($isGuest) {
            $data['author_password'] = password_hash($this->request->getPost('author_password'), PASSWORD_DEFAULT);
        }

        $postId = $this->postModel->insert($data);

        // 파일 업로드
        $files = $this->request->getFiles('attachments') ?? [];
        if ($files && ($board['allow_file'] || $board['allow_image'])) {
            $uploadedFiles = is_array($files['attachments'] ?? null) ? $files['attachments'] : ($files ? [$files] : []);
            // CI4 다중파일은 getFileMultiple 사용
            $multiFiles = $this->request->getFileMultiple('attachments');
            if ($multiFiles) {
                $this->uploader->savePostFiles($postId, $multiFiles);
            }
        }

        return redirect()->to("/board/{$boardSlug}/{$postId}")->with('success', '게시글이 등록되었습니다.');
    }

    // ─── 수정 ───────────────────────────────────────────────────────────────

    public function edit(string $boardSlug, int $postId)
    {
        $board = $this->boardModel->getBySlug($boardSlug);
        $post  = $this->postModel->getDetail($postId);

        if (! $board || ! $post) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        if (! $this->canEditPost($post)) {
            return redirect()->back()->with('error', '수정 권한이 없습니다.');
        }

        $files = $this->fileModel->getByPost($postId);
        return $this->render('board/write', compact('board', 'post', 'files'));
    }

    public function update(string $boardSlug, int $postId)
    {
        $board = $this->boardModel->getBySlug($boardSlug);
        $post  = $this->postModel->find($postId);

        if (! $board || ! $post || ! $this->canEditPost($post)) {
            return redirect()->back()->with('error', '권한이 없습니다.');
        }

        $this->postModel->update($postId, [
            'title'     => $this->request->getPost('title'),
            'content'   => $this->request->getPost('content'),
            'is_notice' => $this->getUserRole() === 'admin' ? (int) $this->request->getPost('is_notice') : $post['is_notice'],
            'is_secret' => (int) $this->request->getPost('is_secret'),
        ]);

        // 파일 추가 업로드
        $multiFiles = $this->request->getFileMultiple('attachments');
        if ($multiFiles) {
            $this->uploader->savePostFiles($postId, $multiFiles);
        }

        // 선택 파일 삭제
        $deleteFileIds = $this->request->getPost('delete_files') ?? [];
        foreach ($deleteFileIds as $fileId) {
            $file = $this->fileModel->find((int) $fileId);
            if ($file && $file['post_id'] == $postId) {
                $this->uploader->deleteFile($file);
            }
        }

        return redirect()->to("/board/{$boardSlug}/{$postId}")->with('success', '수정되었습니다.');
    }

    // ─── 삭제 ───────────────────────────────────────────────────────────────

    public function delete(string $boardSlug, int $postId)
    {
        $board = $this->boardModel->getBySlug($boardSlug);
        $post  = $this->postModel->find($postId);

        if (! $board || ! $post || ! $this->canEditPost($post)) {
            return redirect()->back()->with('error', '권한이 없습니다.');
        }

        $this->fileModel->deleteByPost($postId);
        $this->postModel->delete($postId);

        return redirect()->to("/board/{$boardSlug}")->with('success', '삭제되었습니다.');
    }

    // ─── 파일 다운로드 ───────────────────────────────────────────────────────

    public function download(int $fileId)
    {
        $file = $this->fileModel->find($fileId);
        if (! $file) {
            return redirect()->back()->with('error', '파일을 찾을 수 없습니다.');
        }

        $fullPath = FCPATH . $file['file_path'];
        if (! file_exists($fullPath)) {
            return redirect()->back()->with('error', '파일이 존재하지 않습니다.');
        }

        $this->fileModel->incrementDownload($fileId);

        return $this->response
            ->setHeader('Content-Disposition', 'attachment; filename="' . rawurlencode($file['original_name']) . '"')
            ->setHeader('Content-Type', 'application/octet-stream')
            ->setHeader('Content-Length', filesize($fullPath))
            ->setBody(file_get_contents($fullPath));
    }

    // ─── 댓글 ───────────────────────────────────────────────────────────────

    public function commentStore(string $boardSlug, int $postId)
    {
        $board = $this->boardModel->getBySlug($boardSlug);
        if (! $board || ! $this->checkPermission($board['write_permission'])) {
            return redirect()->back()->with('error', '권한이 없습니다.');
        }

        $isGuest = ! session()->get('user_id');
        $rules   = ['content' => 'required'];
        if ($isGuest) {
            $rules['author_name']     = 'required';
            $rules['author_password'] = 'required|min_length[4]';
        }

        if (! $this->validate($rules)) {
            return redirect()->back()->with('errors', $this->validator->getErrors());
        }

        $this->commentModel->insert([
            'post_id'         => $postId,
            'user_id'         => session()->get('user_id'),
            'content'         => $this->request->getPost('content'),
            'author_name'     => $isGuest ? $this->request->getPost('author_name') : session()->get('user_nickname'),
            'author_password' => $isGuest ? password_hash($this->request->getPost('author_password'), PASSWORD_DEFAULT) : null,
            'ip_address'      => $this->request->getIPAddress(),
        ]);

        return redirect()->to("/board/{$boardSlug}/{$postId}#comments")->with('success', '댓글이 등록되었습니다.');
    }

    public function commentDelete(string $boardSlug, int $postId, int $commentId)
    {
        $comment = $this->commentModel->find($commentId);
        if (! $comment) {
            return redirect()->back()->with('error', '댓글을 찾을 수 없습니다.');
        }

        $role = $this->getUserRole();
        $userId = session()->get('user_id');

        $canDelete = $role === 'admin'
            || ($userId && $comment['user_id'] == $userId);

        if (! $canDelete) {
            return redirect()->back()->with('error', '삭제 권한이 없습니다.');
        }

        $this->commentModel->delete($commentId);
        return redirect()->to("/board/{$boardSlug}/{$postId}#comments");
    }

    // ─── 내부 헬퍼 ──────────────────────────────────────────────────────────

    private function canEditPost(array $post): bool
    {
        $role   = $this->getUserRole();
        $userId = session()->get('user_id');

        if ($role === 'admin') return true;
        if ($userId && $post['user_id'] == $userId) return true;

        // 비회원: POST로 넘어온 비밀번호 검증
        if (! $userId && $post['author_password']) {
            $inputPw = $this->request->getPost('author_password');
            return $inputPw && password_verify($inputPw, $post['author_password']);
        }

        return false;
    }

    private function canAccessSecret(array $post): bool
    {
        $role   = $this->getUserRole();
        $userId = session()->get('user_id');

        return $role === 'admin' || ($userId && $post['user_id'] == $userId);
    }
}
