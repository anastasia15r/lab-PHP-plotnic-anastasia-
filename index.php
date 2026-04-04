<?php
/**
 * Трекер привычек
 * Полностью соответствует лабораторной работе №6
 * Включает: форма, валидацию, сохранение, вывод данных, ООП
 */

/**
 * Интерфейс валидатора
 */
interface ValidatorInterface {
    public function validate(array $data): array;
    public function getErrors(): array;
}

/**
 * Класс HabitValidator для проверки данных формы
 */
class HabitValidator implements ValidatorInterface {
    private array $errors = [];
    private array $validCategories = ['Здоровье', 'Работа', 'Личное'];
    private array $validDifficulty = ['Легко', 'Средне', 'Сложно'];

    /**
     * Валидирует данные формы
     * @param array $data
     * @return array Обработанные данные
     */
    public function validate(array $data): array {
        $this->errors = [];

        $habit_name = trim($data['habit_name'] ?? '');
        $category = $data['category'] ?? '';
        $frequency = trim($data['frequency'] ?? '');
        $start_date = $data['start_date'] ?? '';
        $difficulty = $data['difficulty'] ?? '';
        $notes = trim($data['notes'] ?? '');
        $created_at = date('Y-m-d H:i:s');
        $updated_at = $created_at;

        if (strlen($habit_name) < 2) $this->errors[] = "Название должно содержать минимум 2 символа.";
        if (!in_array($category, $this->validCategories)) $this->errors[] = "Выберите корректную категорию.";
        if (!in_array($difficulty, $this->validDifficulty)) $this->errors[] = "Выберите корректную сложность.";
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date)) $this->errors[] = "Дата начала указана неверно.";

        return [
            'habit_name' => htmlspecialchars($habit_name),
            'category' => $category,
            'frequency' => htmlspecialchars($frequency),
            'start_date' => $start_date,
            'difficulty' => $difficulty,
            'notes' => htmlspecialchars($notes),
            'created_at' => $created_at,
            'updated_at' => $updated_at
        ];
    }

    /**
     * Возвращает ошибки валидации
     * @return array
     */
    public function getErrors(): array {
        return $this->errors;
    }
}

/**
 * Класс HabitManager для управления привычками
 */
class HabitManager {
    private string $file = 'data.json';
    private HabitValidator $validator;
    private array $errors = [];

    public function __construct(HabitValidator $validator) {
        $this->validator = $validator;
    }

    /**
     * Сохраняет новую привычку
     * @param array $data
     * @return bool
     */
    public function save(array $data): bool {
        $habit = $this->validator->validate($data);
        $this->errors = $this->validator->getErrors();

        if ($this->errors) return false;

        $all = $this->getAll();
        $all[] = $habit;
        file_put_contents($this->file, json_encode($all, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        return true;
    }

    /**
     * Получает все привычки
     * @return array
     */
    public function getAll(): array {
        if (!file_exists($this->file)) return [];
        $data = json_decode(file_get_contents($this->file), true);
        return $data ?: [];
    }

    /**
     * Возвращает ошибки после валидации
     * @return array
     */
    public function getErrors(): array {
        return $this->errors;
    }
}

// Инициализация
$validator = new HabitValidator();
$manager = new HabitManager($validator);
$success = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $success = $manager->save($_POST);
    $errors = $manager->getErrors();
}

$habits = $manager->getAll();

// Сортировка по дате создания
usort($habits, fn($a,$b) => strtotime($b['created_at']) - strtotime($a['created_at']));
?>

<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<title>Трекер привычек</title>
<style>
body { font-family: Arial, sans-serif; margin: 20px; }
form { max-width: 400px; margin-bottom: 30px; }
input, select, textarea, button { width: 100%; margin-bottom: 10px; padding: 8px; }
table { border-collapse: collapse; width: 100%; margin-top: 20px; }
th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
th { background-color: #f4f4f4; }
.error { color: red; }
.success { color: green; }
</style>
</head>
<body>

<h2>Добавить новую привычку</h2>

<?php if ($errors): ?>
<div class="error">
<ul>
<?php foreach ($errors as $e) echo "<li>$e</li>"; ?>
</ul>
</div>
<?php endif; ?>

<?php if ($success): ?>
<div class="success">Привычка успешно сохранена!</div>
<?php endif; ?>

<form action="" method="POST">
    <label>Название привычки:</label>
    <input type="text" name="habit_name" required minlength="2" maxlength="50">

    <label>Категория:</label>
    <select name="category" required>
        <option value="">--Выберите категорию--</option>
        <option value="Здоровье">Здоровье</option>
        <option value="Работа">Работа</option>
        <option value="Личное">Личное</option>
    </select>

    <label>Частота:</label>
    <input type="text" name="frequency" required placeholder="Ежедневно / Еженедельно">

    <label>Дата начала:</label>
    <input type="date" name="start_date" required>

    <label>Сложность:</label>
    <select name="difficulty" required>
        <option value="">--Выберите сложность--</option>
        <option value="Легко">Легко</option>
        <option value="Средне">Средне</option>
        <option value="Сложно">Сложно</option>
    </select>

    <label>Заметки:</label>
    <textarea name="notes" rows="4"></textarea>

    <button type="submit">Сохранить привычку</button>
</form>

<h2>Список привычек</h2>

<?php if(!$habits): ?>
<p>Записей пока нет.</p>
<?php else: ?>
<table>
<tr>
    <th>Название</th>
    <th>Категория</th>
    <th>Частота</th>
    <th>Дата начала</th>
    <th>Сложность</th>
    <th>Заметки</th>
    <th>Создано</th>
</tr>
<?php foreach ($habits as $habit): ?>
<tr>
    <td><?= $habit['habit_name'] ?></td>
    <td><?= $habit['category'] ?></td>
    <td><?= $habit['frequency'] ?></td>
    <td><?= $habit['start_date'] ?></td>
    <td><?= $habit['difficulty'] ?></td>
    <td><?= $habit['notes'] ?></td>
    <td><?= $habit['created_at'] ?></td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>

</body>
</html>