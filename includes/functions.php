<?php
function addNoteTag($noteId, $tagName, $userId) {
    $tag = fetchOne("SELECT id FROM tags WHERE name = ? AND user_id = ?", [$tagName, $userId], 'si');
    
    if (!$tag) {
        $tagId = insert('tags', [
            'name' => $tagName,
            'user_id' => $userId
        ]);
    } else {
        $tagId = $tag['id'];
    }
    
    $tagAssigned = fetchOne("SELECT * FROM note_tags WHERE note_id = ? AND tag_id = ?", [$noteId, $tagId], 'ii');
    
    if ($tagAssigned) {
        return true;
    }
    
    return insert('note_tags', [
        'note_id' => $noteId,
        'tag_id' => $tagId
    ]) !== false;
}

function getNoteTags($noteId) {
    $sql = "SELECT t.* 
            FROM tags t
            JOIN note_tags nt ON t.id = nt.tag_id
            WHERE nt.note_id = ?
            ORDER BY t.name";
    
    return fetchAll($sql, [$noteId], 'i');
}

function getNotesByTag($tagId, $userId) {
    $sql = "SELECT n.*, s.name as subject_name, s.color as subject_color
            FROM notes n
            JOIN note_tags nt ON n.id = nt.note_id
            JOIN subjects s ON n.subject_id = s.id
            WHERE nt.tag_id = ? AND n.user_id = ?
            ORDER BY n.created_at DESC";
    
    return fetchAll($sql, [$tagId, $userId], 'ii');
}

function removeNoteTag($noteId, $tagId) {
    return delete('note_tags', 'note_id = ? AND tag_id = ?', [$noteId, $tagId]);
}

function getUserNotes($userId, $subjectId = null) {
    $params = [$userId];
    $types = 'i';
    
    $sql = "SELECT n.*, s.name as subject_name, s.color as subject_color
            FROM notes n
            JOIN subjects s ON n.subject_id = s.id
            WHERE n.user_id = ?";
    
    if ($subjectId !== null) {
        $sql .= " AND n.subject_id = ?";
        $params[] = $subjectId;
        $types .= 'i';
    }
    
    $sql .= " ORDER BY n.updated_at DESC";
    
    return fetchAll($sql, $params, $types);
}

function getNote($noteId, $userId) {
    $sql = "SELECT n.*, s.name as subject_name, s.color as subject_color
            FROM notes n
            JOIN subjects s ON n.subject_id = s.id
            WHERE n.id = ? AND (n.user_id = ? OR n.is_public = 1)";
    
    return fetchOne($sql, [$noteId, $userId], 'ii');
}

function updateNote($noteId, $userId, $data) {
    $note = fetchOne("SELECT id FROM notes WHERE id = ? AND user_id = ?", [$noteId, $userId], 'ii');
    
    if (!$note) {
        return false;
    }
    
    if (isset($data['content'])) {
        $data['summary'] = generateSummary($data['content']);
        // Log per debug
        error_log("EduNote: Riassunto generato per nota $noteId: " . substr($data['summary'], 0, 50) . "...");
    }
    
    return update('notes', $data, 'id = ?', [$noteId]);
}

function deleteNote($noteId, $userId) {
    $note = fetchOne("SELECT id FROM notes WHERE id = ? AND user_id = ?", [$noteId, $userId], 'ii');
    
    if (!$note) {
        return false;
    }
    
    return delete('notes', 'id = ?', [$noteId]);
}

function getUserStudyGroups($userId) {
    $sql = "SELECT sg.*, gm.is_admin, 
            (SELECT COUNT(*) FROM group_members WHERE group_id = sg.id) as member_count
            FROM study_groups sg
            JOIN group_members gm ON sg.id = gm.group_id
            WHERE gm.user_id = ?
            ORDER BY sg.name";
    
    return fetchAll($sql, [$userId], 'i');
}

function getStudyGroup($groupId) {
    return fetchOne("SELECT * FROM study_groups WHERE id = ?", [$groupId], 'i');
}

function getGroupMembers($groupId) {
    $sql = "SELECT u.id, u.username, u.full_name, u.profile_image, gm.is_admin, gm.joined_at
            FROM group_members gm
            JOIN users u ON gm.user_id = u.id
            WHERE gm.group_id = ?
            ORDER BY gm.is_admin DESC, u.username";
    
    return fetchAll($sql, [$groupId], 'i');
}

function isGroupMember($groupId, $userId) {
    $member = fetchOne("SELECT * FROM group_members WHERE group_id = ? AND user_id = ?", [$groupId, $userId], 'ii');
    
    return $member !== false;
}

function isGroupAdmin($groupId, $userId) {
    $admin = fetchOne("SELECT * FROM group_members WHERE group_id = ? AND user_id = ? AND is_admin = 1", [$groupId, $userId], 'ii');
    
    return $admin !== false;
}

function addGroupMember($groupId, $username, $isAdmin = false) {
    $user = fetchOne("SELECT id FROM users WHERE username = ? OR email = ?", [$username, $username], 'ss');
    
    if (!$user) {
        return false;
    }
    
    $userId = $user['id'];
    
    if (isGroupMember($groupId, $userId)) {
        return true;
    }
    
    return insert('group_members', [
        'group_id' => $groupId,
        'user_id' => $userId,
        'is_admin' => $isAdmin ? 1 : 0
    ]) !== false;
}

function removeGroupMember($groupId, $userId, $requesterId) {
    if (!isGroupAdmin($groupId, $requesterId)) {
        return false;
    }
    
    if (isGroupAdmin($groupId, $userId)) {
        $adminCount = fetchOne("SELECT COUNT(*) as count FROM group_members WHERE group_id = ? AND is_admin = 1", [$groupId], 'i')['count'];
        
        if ($adminCount <= 1) {
            return false;
        }
    }
    
    return delete('group_members', 'group_id = ? AND user_id = ?', [$groupId, $userId]);
}

function generateToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

function formatDateTime($dateTime, $format = 'd M Y, H:i') {
    if (empty($dateTime)) {
        return 'N/A';
    }

    $timestamp = strtotime($dateTime);

    if ($timestamp === false) {
        return 'Data non valida';
    }

    return date($format, $timestamp);
}

function searchNotes($userId, $keyword) {
    $keyword = "%$keyword%";
    
    $sql = "SELECT n.*, s.name as subject_name, s.color as subject_color
            FROM notes n
            JOIN subjects s ON n.subject_id = s.id
            WHERE n.user_id = ? AND (n.title LIKE ? OR n.content LIKE ? OR n.summary LIKE ?)
            ORDER BY n.updated_at DESC";
    
    return fetchAll($sql, [$userId, $keyword, $keyword, $keyword], 'isss');
}

function getRecentNotes($userId, $limit = 5) {
    $sql = "SELECT n.*, s.name as subject_name, s.color as subject_color
            FROM notes n
            JOIN subjects s ON n.subject_id = s.id
            WHERE n.user_id = ?
            ORDER BY n.updated_at DESC
            LIMIT ?";
    
    return fetchAll($sql, [$userId, $limit], 'ii');
}

function getSubjectStats($userId) {
    $sql = "SELECT s.id, s.name, s.color, COUNT(n.id) as note_count
            FROM subjects s
            LEFT JOIN notes n ON s.id = n.subject_id AND n.user_id = s.user_id
            WHERE s.user_id = ?
            GROUP BY s.id
            ORDER BY note_count DESC";
    
    return fetchAll($sql, [$userId], 'i');
}

function getUserStats($userId) {
    $stats = [];
    
    $stats['total_notes'] = fetchOne("SELECT COUNT(*) as count FROM notes WHERE user_id = ?", [$userId], 'i')['count'];
    $stats['total_subjects'] = fetchOne("SELECT COUNT(*) as count FROM subjects WHERE user_id = ?", [$userId], 'i')['count'];
    
    $firstDayOfMonth = date('Y-m-01');
    $stats['notes_this_month'] = fetchOne(
        "SELECT COUNT(*) as count FROM notes WHERE user_id = ? AND created_at >= ?", 
        [$userId, $firstDayOfMonth], 
        'is'
    )['count'];
    
    $today = date('Y-m-d');
    $stats['notes_for_review'] = fetchOne(
        "SELECT COUNT(*) as count FROM review_schedule WHERE user_id = ? AND next_review_date <= ?", 
        [$userId, $today], 
        'is'
    )['count'];
    
    return $stats;
}

function displayFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);

        $type = $message['type'];
        $text = $message['message'];

        $icon = '';
        switch ($type) {
            case 'success':
                $icon = '<i class="fas fa-check-circle me-2"></i>';
                break;
            case 'error':
                $icon = '<i class="fas fa-exclamation-circle me-2"></i>';
                break;
            case 'info':
                $icon = '<i class="fas fa-info-circle me-2"></i>';
                break;
            case 'warning':
                $icon = '<i class="fas fa-exclamation-triangle me-2"></i>';
                break;
        }

        return "<div class='alert alert-{$type}'>{$icon}{$text}</div>";
    }

    return null;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);  
}

function getCurrentUser() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    return $_SESSION['user'] ?? null;
}

function getUserSubjects($userId) {
    $sql = "SELECT * FROM subjects WHERE user_id = ? ORDER BY name";
    return fetchAll($sql, [$userId], 'i');
}

function createSubject($userId, $name, $description = '', $color = '#3498db') {
    $args = func_get_args();
    if (count($args) === 3) {
        $color = $description;
        $description = '';
    }
    
    if (empty($name)) {
        return false;
    }
    
    if (strlen($color) > 7) {
        $color = substr($color, 0, 7);
    }

    $data = [
        'user_id' => $userId,
        'name' => $name,
        'description' => $description,
        'color' => $color
    ];
    
    return insert('subjects', $data);
}

function createReviewSchedule($userId, $noteId) {
    $nextReviewDate = date('Y-m-d', strtotime('+1 day'));

    return insert('review_schedule', [
        'user_id' => $userId,
        'note_id' => $noteId,
        'next_review_date' => $nextReviewDate,
        'review_count' => 0
    ]);
}

function calculateSpacedInterval($reviewCount) {
    $intervals = [1, 3, 7, 14, 30, 60, 120];
    $index = min($reviewCount - 1, count($intervals) - 1);
    return $intervals[$index];
}

function completeReview($reviewId) {
    $review = fetchOne("SELECT * FROM review_schedule WHERE id = ?", [$reviewId], 'i');

    if (!$review) {
        return false;
    }

    $reviewCount = $review['review_count'] + 1;
    $daysToAdd = calculateSpacedInterval($reviewCount);
    $nextReviewDate = date('Y-m-d', strtotime("+$daysToAdd days"));

    return update('review_schedule', [
        'next_review_date' => $nextReviewDate,
        'review_count' => $reviewCount
    ], 'id = ?', [$reviewId]);
}

function getTodayReviewNotes($userId) {
    $today = date('Y-m-d');

    $sql = "SELECT n.*, s.name as subject_name, s.color as subject_color, r.id as review_id, r.review_count
            FROM review_schedule r
            JOIN notes n ON r.note_id = n.id
            JOIN subjects s ON n.subject_id = s.id
            WHERE r.user_id = ? AND r.next_review_date <= ?
            ORDER BY r.next_review_date ASC";

    $result = fetchAll($sql, [$userId, $today], 'is');
    return $result ?: [];
}

function logoutUser() {
    unset($_SESSION['user_id']);
    session_destroy();
}

function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function loginUser($username, $password) {
    $user = fetchOne("SELECT * FROM users WHERE username = ? OR email = ?", [$username, $username], 'ss');

    if (!$user) {
        return false;
    }

    if (password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user'] = [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'full_name' => $user['full_name']
        ];

        update('users', ['last_login' => date('Y-m-d H:i:s')], 'id = ?', [$user['id']]);
        return true;
    }

    return false;
}

function registerUser($username, $email, $password, $fullName) {
    $existing = fetchOne("SELECT id FROM users WHERE username = ? OR email = ?", [$username, $email], 'ss');
    
    if ($existing) {
        return false;
    }
    
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => HASH_COST]);
    $apiKey = generateToken(32);
    
    $data = [
        'username' => $username,
        'email' => $email,
        'password' => $hashedPassword,
        'full_name' => $fullName,
        'api_key' => $apiKey
    ];
    
    $userId = insert('users', $data);
    
    if ($userId) {
        $_SESSION['user'] = [
            'id' => $userId,
            'username' => $username,
            'email' => $email,
            'full_name' => $fullName,
            'api_key' => $apiKey,
            'registration_date' => date('Y-m-d H:i:s'),
            'profile_image' => null
        ];
        
        return $userId;
    }
    
    return false;
}

function createNote($userId, $subjectId, $title, $content, $isPublic = false) {
    $summary = generateSummary($content);
    
    $data = [
        'user_id' => $userId,
        'subject_id' => $subjectId,
        'title' => $title,
        'content' => $content,
        'summary' => $summary,
        'is_public' => $isPublic ? 1 : 0
    ];
    
    $noteId = insert('notes', $data);
    
    if ($noteId) {
        createReviewSchedule($userId, $noteId);
    }
    
    return $noteId;
}

function generateSummary($content, $useAI = true) {
    $plainText = strip_tags($content);
    
    error_log("EduNote: Generazione riassunto per testo di " . strlen($plainText) . " caratteri");
    
    if (strlen($plainText) < 200) {
        $summary = substr($plainText, 0, 150);
        if (strlen($plainText) > 150) {
            $summary .= '...';
        }
        error_log("EduNote: Testo troppo breve, riassunto semplice: " . substr($summary, 0, 50) . "...");
        return $summary;
    }
    
    if (!defined('USE_AI_SUMMARY') || !USE_AI_SUMMARY || !$useAI) {
        error_log("EduNote: AI disabilitata, uso riassunto semplice");
        return substr($plainText, 0, 150) . '...';
    }
    
    try {
        $apiKey = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : '';
        
        if (empty($apiKey)) {
            error_log("EduNote: API key OpenAI non configurata");
            throw new Exception('API key not configured');
        }
        
        $text = substr($plainText, 0, 4000);
        
        error_log("EduNote: Chiamata API OpenAI in corso...");
        
        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        $data = [
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Riassumi il seguente testo in 2-3 frasi mantenendo i concetti chiave. Scrivi il riassunto in italiano.'
                ],
                [
                    'role' => 'user',
                    'content' => $text
                ]
            ],
            'max_tokens' => 150,
            'temperature' => 0.5
        ];
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            $error = "Errore curl: " . curl_error($ch);
            error_log("EduNote: $error");
            throw new Exception($error);
        }
        
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode != 200) {
            error_log("EduNote: Errore HTTP $httpCode - Risposta: $response");
            throw new Exception("HTTP error: $httpCode");
        }
        
        $responseData = json_decode($response, true);
        
        if (isset($responseData['error'])) {
            $error = "Errore API: " . $responseData['error']['message'];
            error_log("EduNote: $error");
            throw new Exception($error);
        }
        
        if (isset($responseData['choices'][0]['message']['content'])) {
            $aiSummary = trim($responseData['choices'][0]['message']['content']);
            error_log("EduNote: Riassunto AI generato con successo: " . substr($aiSummary, 0, 50) . "...");
            return $aiSummary;
        } else {
            error_log("EduNote: Riassunto non trovato nella risposta API");
            throw new Exception("No summary in API response");
        }
    } catch (Exception $e) {
        error_log("EduNote: Errore generazione riassunto AI: " . $e->getMessage());
    }
    
    error_log("EduNote: Fallback al riassunto semplice");
    return substr($plainText, 0, 150) . '...';
}

function createStudyGroup($userId, $name, $description) {
    $data = [
        'name' => $name,
        'description' => $description,
        'created_by' => $userId
    ];
    
    $groupId = insert('study_groups', $data);
    
    if ($groupId) {
        addGroupMember($groupId, $userId, true);
    }
    
    return $groupId;
}

function getGroupSharedNotes($groupId) {
    $sql = "SELECT sn.*, n.title, n.content, n.summary, u.username as shared_by_username
            FROM shared_notes sn
            JOIN notes n ON sn.note_id = n.id
            JOIN users u ON sn.shared_by = u.id
            WHERE sn.group_id = ?
            ORDER BY sn.shared_at DESC";
    
    return fetchAll($sql, [$groupId], 'i');
}

function shareNoteWithGroup($noteId, $groupId, $userId) {
    $existing = fetchOne(
        "SELECT id FROM shared_notes WHERE note_id = ? AND group_id = ?",
        [$noteId, $groupId],
        'ii'
    );
    
    if ($existing) {
        return $existing['id'];
    }
    
    $data = [
        'note_id' => $noteId,
        'group_id' => $groupId,
        'shared_by' => $userId
    ];
    
    return insert('shared_notes', $data);
}

function getNoteComments($sharedNoteId) {
    $sql = "SELECT nc.*, u.username, u.profile_image
            FROM note_comments nc
            JOIN users u ON nc.user_id = u.id
            WHERE nc.shared_note_id = ?
            ORDER BY nc.created_at ASC";
    
    return fetchAll($sql, [$sharedNoteId], 'i');
}

function addNoteComment($sharedNoteId, $userId, $comment) {
    $data = [
        'shared_note_id' => $sharedNoteId,
        'user_id' => $userId,
        'comment' => $comment
    ];
    
    return insert('note_comments', $data);
}
?>