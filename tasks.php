false, 'message' => 'Unauthorized']);
        exit;
    } else {
        redirect('../login.php', ['type' => 'error', 'message' => 'Please log in to continue']);
    }
}

// Handle AJAX requests
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => 'Invalid request'];
    
    // Determine request type
    $action = $_POST['action'] ?? ($_GET['action'] ?? 'list');
    
    switch ($action) {
        case 'list':
            // Get all major tasks or filter by period
            $periodId = isset($_GET['period_id']) ? (int)$_GET['period_id'] : null;
            
            $params = [];
            
            if ($periodId !== null && $periodId > 0) {
                $sql = "SELECT t.*, p.name as period_name 
                       FROM MajorTasks t 
                       LEFT JOIN Periods p ON t.period_id = p.id 
                       WHERE t.period_id = ? 
                       ORDER BY t.deadline ASC, t.priority DESC";
                $params[] = $periodId;
            } else {
                $sql = "SELECT t.*, p.name as period_name 
                       FROM MajorTasks t 
                       LEFT JOIN Periods p ON t.period_id = p.id 
                       ORDER BY t.deadline ASC, t.priority DESC";
            }
            
            $tasks = executeQuery($sql, $params);
            $response = ['success' => true, 'data' => $tasks];
            break;
            
        case 'get':
            // Get single major task with subtasks
            $id = (int)($_POST['id'] ?? ($_GET['id'] ?? 0));
            if ($id > 0) {
                $sql = "SELECT t.*, p.name as period_name 
                       FROM MajorTasks t 
                       LEFT JOIN Periods p ON t.period_id = p.id 
                       WHERE t.id = ?";
                $task = getRecord($sql, [$id]);
                
                if ($task) {
                    // Get subtasks
                    $sql = "SELECT * FROM SubTasks WHERE major_task_id = ? ORDER BY deadline ASC, priority DESC";
                    $subtasks = executeQuery($sql, [$id]);
                    $task['subtasks'] = $subtasks;
                    
                    $response = ['success' => true, 'data' => $task];
                } else {
                    $response = ['success' => false, 'message' => 'Task not found'];
                }
            } else {
                $response = ['success' => false, 'message' => 'Invalid task ID'];
            }
            break;
            
        case 'create':
            // Create new major task
            $data = [
                'period_id' => isset($_POST['period_id']) && $_POST['period_id'] > 0 ? (int)$_POST['period_id'] : null,
                'task_name' => sanitizeInput($_POST['task_name'] ?? ''),
                'description' => sanitizeInput($_POST['description'] ?? ''),
                'priority' => sanitizeInput($_POST['priority'] ?? 'Medium'),
                'urgency' => sanitizeInput($_POST['urgency'] ?? 'Normal'),
                'importance' => sanitizeInput($_POST['importance'] ?? 'Normal'),
                'deadline' => sanitizeInput($_POST['deadline'] ?? null),
                'status' => sanitizeInput($_POST['status'] ?? 'To Do'),
                'percent_complete' => min(100, max(0, (int)($_POST['percent_complete'] ?? 0))),
                'working_with' => sanitizeInput($_POST['working_with'] ?? ''),
                'notes' => sanitizeInput($_POST['notes'] ?? '')
            ];
            
            // Validate input
            $errors = [];
            
            if (empty($data['task_name'])) {
                $errors[] = 'Task name is required';
            }
            
            if (empty($errors)) {
                $data['created_at'] = date('Y-m-d H:i:s');
                $data['updated_at'] = date('Y-m-d H:i:s');
                
                $taskId = insertRecord('MajorTasks', $data);
                
                if ($taskId) {
                    logActivity('task_created', "Created major task '{$data['task_name']}'");
                    $response = [
                        'success' => true, 
                        'message' => 'Task created successfully', 
                        'id' => $taskId
                    ];
                } else {
                    $response = ['success' => false, 'message' => 'Failed to create task'];
                }
            } else {
                $response = ['success' => false, 'message' => 'Validation failed', 'errors' => $errors];
            }
            break;
            
        case 'update':
            // Update existing major task
            $id = (int)($_POST['id'] ?? 0);
            
            if ($id <= 0) {
                $response = ['success' => false, 'message' => 'Invalid task ID'];
                break;
            }
            
            $data = [
                'period_id' => isset($_POST['period_id']) && $_POST['period_id'] > 0 ? (int)$_POST['period_id'] : null,
                'task_name' => sanitizeInput($_POST['task_name'] ?? ''),
                'description' => sanitizeInput($_POST['description'] ?? ''),
                'priority' => sanitizeInput($_POST['priority'] ?? 'Medium'),
                'urgency' => sanitizeInput($_POST['urgency'] ?? 'Normal'),
                'importance' => sanitizeInput($_POST['importance'] ?? 'Normal'),
                'deadline' => sanitizeInput($_POST['deadline'] ?? null),
                'status' => sanitizeInput($_POST['status'] ?? 'To Do'),
                'percent_complete' => min(100, max(0, (int)($_POST['percent_complete'] ?? 0))),
                'working_with' => sanitizeInput($_POST['working_with'] ?? ''),
                'notes' => sanitizeInput($_POST['notes'] ?? ''),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            // Validate input
            $errors = [];
            
            if (empty($data['task_name'])) {
                $errors[] = 'Task name is required';
            }
            
            if (empty($errors)) {
                $updated = updateRecord('MajorTasks', $data, 'id = ?', [$id]);
                
                if ($updated) {
                    logActivity('task_updated', "Updated major task ID $id");
                    $response = ['success' => true, 'message' => 'Task updated successfully'];
                } else {
                    $response = ['success' => false, 'message' => 'Failed to update task or no changes made'];
                }
            } else {
                $response = ['success' => false, 'message' => 'Validation failed', 'errors' => $errors];
            }
            break;
            
        case 'delete':
            // Delete major task
            $id = (int)($_POST['id'] ?? 0);
            
            if ($id > 0) {
                // Get task name for logging
                $sql = "SELECT task_name FROM MajorTasks WHERE id = ?";
                $task = getRecord($sql, [$id]);
                
                // Delete task (cascade will delete subtasks due to foreign key)
                $deleted = deleteRecord('MajorTasks', 'id = ?', [$id]);
                
                if ($deleted) {
                    logActivity('task_deleted', "Deleted major task '{$task['task_name']}'");
                    $response = ['success' => true, 'message' => 'Task and all subtasks deleted successfully'];
                } else {
                    $response = ['success' => false, 'message' => 'Failed to delete task'];
                }
            } else {
                $response = ['success' => false, 'message' => 'Invalid task ID'];
            }
            break;
            
        case 'update_status':
            // Quick update of task status
            $id = (int)($_POST['id'] ?? 0);
            $status = sanitizeInput($_POST['status'] ?? '');
            
            if ($id > 0 && !empty($status)) {
                $data = [
                    'status' => $status,
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                // Auto-update percent_complete based on status
                if ($status === 'Completed') {
                    $data['percent_complete'] = 100;
                } else if ($status === 'To Do') {
                    $data['percent_complete'] = 0;
                }
                
                $updated = updateRecord('MajorTasks', $data, 'id = ?', [$id]);
                
                if ($updated) {
                    logActivity('task_status_updated', "Updated major task ID $id status to '$status'");
                    $response = ['success' => true, 'message' => 'Task status updated successfully'];
                } else {
                    $response = ['success' => false, 'message' => 'Failed to update task status'];
                }
            } else {
                $response = ['success' => false, 'message' => 'Invalid task ID or status'];
            }
            break;
            
        case 'update_progress':
            // Quick update of task progress percentage
            $id = (int)($_POST['id'] ?? 0);
            $percent = min(100, max(0, (int)($_POST['percent_complete'] ?? 0)));
            
            if ($id > 0) {
                $data = [
                    'percent_complete' => $percent,
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                // Auto-update status based on percent
                if ($percent === 100) {
                    $data['status'] = 'Completed';
                } else if ($percent === 0) {
                    $data['status'] = 'To Do';
                } else {
                    $data['status'] = 'In Progress';
                }
                
                $updated = updateRecord('MajorTasks', $data, 'id = ?', [$id]);
                
                if ($updated) {
                    logActivity('task_progress_updated', "Updated major task ID $id progress to $percent%");
                    $response = ['success' => true, 'message' => 'Task progress updated successfully'];
                } else {
                    $response = ['success' => false, 'message' => 'Failed to update task progress'];
                }
            } else {
                $response = ['success' => false, 'message' => 'Invalid task ID'];
            }
            break;
            
        default:
            $response = ['success' => false, 'message' => 'Invalid action'];
            break;
    }
    
    echo json_encode($response);
    exit;
}

// Handle GET requests for UI rendering
$action = $_GET['action'] ?? 'list';

switch ($action) {
    case 'create_form':
        $periodId = isset($_GET['period_id']) ? (int)$_GET['period_id'] : null;
        
        // Get all periods for dropdown
        $sql = "SELECT id, name FROM Periods ORDER BY name ASC";
        $periods = executeQuery($sql);
        
        include '../views/major_tasks/create.php';
        break;
        
    case 'edit_form':
        $id = (int)($_GET['id'] ?? 0);
        
        if ($id > 0) {
            $sql = "SELECT * FROM MajorTasks WHERE id = ?";
            $task = getRecord($sql, [$id]);
            
            if ($task) {
                // Get all periods for dropdown
                $sql = "SELECT id, name FROM Periods ORDER BY name ASC";
                $periods = executeQuery($sql);
                
                include '../views/major_tasks/edit.php';
            } else {
                redirect('../major_tasks.php', ['type' => 'error', 'message' => 'Task not found']);
            }
        } else {
            redirect('../major_tasks.php', ['type' => 'error', 'message' => 'Invalid task ID']);
        }
        break;
        
    case 'view':
        $id = (int)($_GET['id'] ?? 0);
        
        if ($id > 0) {
            $sql = "SELECT t.*, p.name as period_name 
                   FROM MajorTasks t 
                   LEFT JOIN Periods p ON t.period_id = p.id 
                   WHERE t.id = ?";
            $task = getRecord($sql, [$id]);
            
            if ($task) {
                // Get subtasks
                $sql = "SELECT * FROM SubTasks WHERE major_task_id = ? ORDER BY deadline ASC, priority DESC";
                $subtasks = executeQuery($sql, [$id]);
                
                include '../views/major_tasks/view.php';
            } else {
                redirect('../major_tasks.php', ['type' => 'error', 'message' => 'Task not found']);
            }
        } else {
            redirect('../major_tasks.php', ['type' => 'error', 'message' => 'Invalid task ID']);
        }
        break;
        
    default:
        redirect('../major_tasks.php');
        break;
}
?>