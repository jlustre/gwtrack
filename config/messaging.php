<?php

return [
    'delete_for_everyone_minutes' => (int) env('MESSAGING_DELETE_FOR_EVERYONE_MINUTES', 60),

    'allowed_attachment_mimes' => [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'video/mp4',
        'audio/mpeg',
        'audio/wav',
    ],

    'max_attachment_size_kb' => (int) env('MESSAGING_MAX_ATTACHMENT_KB', 10240),

    'usage_policy_notice' => 'This messaging tool is for business-related topics only — including mentoring, licensing, training, team coordination, and professional development. Personal or off-topic use may result in a messaging suspension.',

    'reactions' => [
        '👍', '👎', '❤️', '🧡', '💛', '💚', '💙', '💜',
        '🎉', '🔥', '✨', '⭐', '💯', '✅', '❌', '💡',
        '🙏', '👏', '🙌', '🤝', '💪', '🚀', '🎯', '📌',
        '😂', '😊', '😅', '😢', '😮', '😡', '🤔', '👀',
        '💬', '📎', '📅', '⏰', '🏆', '🎓', '📈', '💼',
    ],
];
