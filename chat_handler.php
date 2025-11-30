<?php
// Имя файла, где будет храниться вся история чата
$chat_file = 'dialog_history.txt';

// Устанавливаем заголовки для JSON-ответа
header('Content-Type: application/json');

// Проверка типа запроса
$action = $_REQUEST['action'] ?? '';

switch ($action) {
    case 'send':
        // --- Обработка отправки сообщения ---
        $user = $_POST['user'] ?? 'Неизвестный';
        $text = $_POST['text'] ?? '';
        
        // Очистка текста от потенциально опасных символов (хотя для TXT это не так критично)
        $user = htmlspecialchars($user);
        $text = htmlspecialchars($text);
        
        if (!empty($user) && !empty($text)) {
            // Форматируем сообщение
            $timestamp = date("H:i");
            $message_line = "[$timestamp] $user: $text\n";

            // Добавляем сообщение в конец файла
            // FILE_APPEND гарантирует, что мы добавляем, а не перезаписываем
            // LOCK_EX предотвращает одновременную запись
            if (file_put_contents($chat_file, $message_line, FILE_APPEND | LOCK_EX) !== false) {
                echo json_encode(['status' => 'success', 'message' => 'Сообщение сохранено.']);
            } else {
                // Это может произойти, если нет прав на запись в файл
                echo json_encode(['status' => 'error', 'message' => 'Ошибка записи файла на сервере. Проверьте права доступа.']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Пустое сообщение.']);
        }
        break;

    case 'get':
        // --- Обработка получения истории ---
        if (file_exists($chat_file)) {
            // Читаем все содержимое файла
            $history = file_get_contents($chat_file);
            // Разбиваем историю на отдельные строки (сообщения)
            $messages = array_filter(explode("\n", $history));
            
            // Удаляем временные метки для более чистого отображения во фронтенде
            $clean_messages = [];
            foreach ($messages as $msg) {
                // Убираем "[ЧЧ:ММ] " из начала каждой строки
                $clean_messages[] = preg_replace('/^\[\d{2}:\d{2}\]\s*/', '', $msg);
            }

            echo json_encode(['status' => 'success', 'messages' => $clean_messages]);
        } else {
            // Если файл не существует, возвращаем пустой массив (это нормально)
            echo json_encode(['status' => 'success', 'messages' => []]);
        }
        break;

    default:
        // Неизвестное действие
        echo json_encode(['status' => 'error', 'message' => 'Неизвестное действие.']);
        break;
}
?>