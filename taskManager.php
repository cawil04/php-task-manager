<?php
$conn = mysqli_connect("localhost", "root", "", "task_manager");
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$edit_mode = false;
$edit_task = [
    'id' => '',
    'title' => '',
    'description' => '',
    'due_date' => ''
];

if (isset($_POST['add'])) {
    $title = trim($_POST['title'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $due = trim($_POST['due_date'] ?? '');

    if ($title !== '') {
        $sql = "INSERT INTO tasks (title, description, due_date) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "sss", $title, $desc, $due);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

if (isset($_POST['update'])) {
    $id = (int)($_POST['id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $due = trim($_POST['due_date'] ?? '');

    if ($title !== '' && $id > 0) {
        $sql = "UPDATE tasks SET title=?, description=?, due_date=? WHERE id=?";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "sssi", $title, $desc, $due, $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

if (isset($_POST['delete'])) {
    $id = (int)($_POST['id'] ?? 0);

    if ($id > 0) {
        $sql = "DELETE FROM tasks WHERE id=?";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

if (isset($_POST['toggle'])) {
    $id = (int)($_POST['id'] ?? 0);

    if ($id > 0) {
        $sql = "UPDATE tasks SET is_complete = 1 - is_complete WHERE id=?";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];

    if ($edit_id > 0) {
        $sql = "SELECT * FROM tasks WHERE id=?";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $edit_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $edit_task = mysqli_fetch_assoc($result) ?: $edit_task;
            mysqli_stmt_close($stmt);
            $edit_mode = true;
        }
    }
}

$tasks = mysqli_query($conn, "SELECT * FROM tasks ORDER BY created_at DESC");
if (!$tasks) {
    die("Query failed: " . mysqli_error($conn));
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Simple PHP Task Manager</title>
    <style>
        * {
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            margin: 0;
            background: linear-gradient(135deg, #f4f7fb, #eaf2ff);
            color: #1f2937;
        }

        .container {
            max-width: 1100px;
            margin: 40px auto;
            padding: 20px;
        }

        .panel {
            background: #ffffff;
            border-radius: 18px;
            box-shadow: 0 10px 30px rgba(31, 41, 55, 0.08);
            padding: 25px;
            margin-bottom: 25px;
        }

        h1, h2 {
            margin-top: 0;
            color: #111827;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .field {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .field.full {
            grid-column: 1 / -1;
        }

        label {
            font-weight: 600;
            color: #374151;
        }

        input, textarea, button, a.btn {
            border-radius: 10px;
            border: 1px solid #d1d5db;
            padding: 12px 14px;
            font-size: 15px;
        }

        input:focus, textarea:focus {
            outline: none;
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12);
        }

        textarea {
            min-height: 100px;
            resize: vertical;
        }

        .actions {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-top: 10px;
        }

        .btn, button, input[type="submit"] {
            background: #4f46e5;
            color: white;
            border: none;
            cursor: pointer;
            transition: 0.2s ease;
            font-weight: 600;
        }

        .btn:hover, button:hover, input[type="submit"]:hover {
            background: #4338ca;
        }

        .btn-secondary {
            background: #e5e7eb;
            color: #111827;
            text-decoration: none;
            display: inline-block;
            padding: 12px 16px;
        }

        .btn-secondary:hover {
            background: #d1d5db;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            overflow: hidden;
            border-radius: 12px;
        }

        th, td {
            padding: 14px 12px;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
            vertical-align: top;
        }

        th {
            background: #f3f4f6;
            color: #374151;
        }

        tr:hover {
            background: #f9fafb;
        }

        .status {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            background: #ecfdf5;
            color: #047857;
        }

        .status.pending {
            background: #fff7ed;
            color: #c2410c;
        }

        .task-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
        }

        .mini-btn {
            background: #e0e7ff;
            color: #3730a3;
            border: none;
            padding: 8px 10px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }

        .mini-btn.delete {
            background: #fee2e2;
            color: #b91c1c;
        }

        .mini-btn.link {
            background: transparent;
            color: #4f46e5;
            text-decoration: none;
            padding: 8px 10px;
            border-radius: 8px;
        }

        @media (max-width: 700px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            .container {
                padding: 12px;
            }

            table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="panel">
            <h1>Task Manager</h1>

            <form method="post">
                <?php if ($edit_mode): ?>
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($edit_task['id'], ENT_QUOTES, 'UTF-8'); ?>">
                <?php endif; ?>

                <div class="form-grid">
                    <div class="field full">
                        <label>Title:</label>
                        <input type="text" name="title" value="<?php echo htmlspecialchars($edit_mode ? $edit_task['title'] : '', ENT_QUOTES, 'UTF-8'); ?>">
                    </div>

                    <div class="field full">
                        <label>Description:</label>
                        <textarea name="description"><?php echo htmlspecialchars($edit_mode ? $edit_task['description'] : '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </div>

                    <div class="field full">
                        <label>Due Date:</label>
                        <input type="date" name="due_date" value="<?php echo htmlspecialchars($edit_mode ? $edit_task['due_date'] : '', ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                </div>

                <div class="actions">
                    <?php if ($edit_mode): ?>
                        <input type="submit" name="update" value="Update Task">
                        <a class="btn btn-secondary" href="<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>">Cancel</a>
                    <?php else: ?>
                        <input type="submit" name="add" value="Add Task">
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="panel">
            <h2>All Tasks</h2>
            <table>
                <tr>
                    <th>Title</th>
                    <th>Description</th>
                    <th>Due Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>

                <?php while ($t = mysqli_fetch_assoc($tasks)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($t['title'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo nl2br(htmlspecialchars($t['description'], ENT_QUOTES, 'UTF-8')); ?></td>
                        <td><?php echo htmlspecialchars($t['due_date'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <span class="status <?php echo $t['is_complete'] ? '' : 'pending'; ?>">
                                <?php echo $t['is_complete'] ? 'Done' : 'Pending'; ?>
                            </span>
                        </td>
                        <td>
                            <div class="task-actions">
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($t['id'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <button class="mini-btn" type="submit" name="toggle"><?php echo $t['is_complete'] ? 'Undo' : 'Done'; ?></button>
                                </form>

                                <a class="mini-btn link" href="?edit=<?php echo htmlspecialchars($t['id'], ENT_QUOTES, 'UTF-8'); ?>">Edit</a>

                                <form method="post" style="display:inline;" onsubmit="return confirm('Delete this task?');">
                                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($t['id'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <button class="mini-btn delete" type="submit" name="delete">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </table>
        </div>
    </div>
</body>
</html>