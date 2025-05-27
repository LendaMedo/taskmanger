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
            // Get all periods
            $sql = "SELECT * FROM Periods ORDER BY start_date DESC";
            $periods = executeQuery($sql);
            $response = ['success' => true, 'data' => $periods];
            break;
            
        case 'get':
            // Get single period
            $id = (int)($_POST['id'] ?? ($_GET['id'] ?? 0));
            if ($id > 0) {
                $sql = "SELECT * FROM Periods WHERE id = ?";
                $period = getRecord($sql, [$id]);
                
                if ($period) {
                    $response = ['success' => true, 'data' => $period];
                } else {
                    $response = ['success' => false, 'message' => 'Period not found'];
                }
            } else {
                $response = ['success' => false, 'message' => 'Invalid period ID'];
            }
            break;
            
        case 'create':
            // Create new period
            $name = sanitizeInput($_POST['name'] ?? '');
            $startDate = sanitizeInput($_POST['start_date'] ?? '');
            $endDate = sanitizeInput($_POST['end_date'] ?? '');
            
            // Validate input
            $errors = [];
            
            if (empty($name)) {
                $errors[] = 'Name is required';
            }
            
            if (empty($errors)) {
                $periodData = [
                    'name' => $name,
                    'start_date' => !empty($startDate) ? $startDate : null,
                    'end_date' => !empty($endDate) ? $endDate : null,
                    'created_at' => date('Y-m-d H:i:s')
                ];
                
                $periodId = insertRecord('Periods', $periodData);
                
                if ($periodId) {
                    logActivity('period_created', "Created period '$name'");
                    $response = [
                        'success' => true, 
                        'message' => 'Period created successfully', 
                        'id' => $periodId
                    ];
                } else {
                    $response = ['success' => false, 'message' => 'Failed to create period'];
                }
            } else {
                $response = ['success' => false, 'message' => 'Validation failed', 'errors' => $errors];
            }
            break;
            
        case 'update':
            // Update existing period
            $id = (int)($_POST['id'] ?? 0);
            $name = sanitizeInput($_POST['name'] ?? '');
            $startDate = sanitizeInput($_POST['start_date'] ?? '');
            $endDate = sanitizeInput($_POST['end_date'] ?? '');
            
            // Validate input
            $errors = [];
            
            if ($id <= 0) {
                $errors[] = 'Invalid period ID';
            }
            
            if (empty($name)) {
                $errors[] = 'Name is required';
            }
            
            if (empty($errors)) {
                $periodData = [
                    'name' => $name,
                    'start_date' => !empty($startDate) ? $startDate : null,
                    'end_date' => !empty($endDate) ? $endDate : null
                ];
                
                $updated = updateRecord('Periods', $periodData, 'id = ?', [$id]);
                
                if ($updated) {
                    logActivity('period_updated', "Updated period ID $id");
                    $response = ['success' => true, 'message' => 'Period updated successfully'];
                } else {
                    $response = ['success' => false, 'message' => 'Failed to update period or no changes made'];
                }
            } else {
                $response = ['success' => false, 'message' => 'Validation failed', 'errors' => $errors];
            }
            break;
            
        case 'delete':
            // Delete period
            $id = (int)($_POST['id'] ?? 0);
            
            if ($id > 0) {
                // Check if period has major tasks
                $sql = "SELECT COUNT(*) as task_count FROM MajorTasks WHERE period_id = ?";
                $result = getRecord($sql, [$id]);
                
                if ($result && $result['task_count'] > 0) {
                    $response = [
                        'success' => false, 
                        'message' => 'Cannot delete period because it has associated tasks'
                    ];
                } else {
                    // Get period name for logging
                    $sql = "SELECT name FROM Periods WHERE id = ?";
                    $period = getRecord($sql, [$id]);
                    
                    $deleted = deleteRecord('Periods', 'id = ?', [$id]);
                    
                    if ($deleted) {
                        logActivity('period_deleted', "Deleted period '{$period['name']}'");
                        $response = ['success' => true, 'message' => 'Period deleted successfully'];
                    } else {
                        $response = ['success' => false, 'message' => 'Failed to delete period'];
                    }
                }
            } else {
                $response = ['success' => false, 'message' => 'Invalid period ID'];
            }
            break;
            
        default:
            $response = ['success' => false, 'message' => 'Invalid action'];
            break;
    }
    
    echo json_encode($response);
    exit;
}

// Non-AJAX requests for rendering forms

// Handle GET requests for UI rendering
$action = $_GET['action'] ?? 'list';

switch ($action) {
    case 'create_form':
        include '../views/periods/create.php';
        break;
        
    case 'edit_form':
        $id = (int)($_GET['id'] ?? 0);
        
        if ($id > 0) {
            $sql = "SELECT * FROM Periods WHERE id = ?";
            $period = getRecord($sql, [$id]);
            
            if ($period) {
                include '../views/periods/edit.php';
            } else {
                redirect('../periods.php', ['type' => 'error', 'message' => 'Period not found']);
            }
        } else {
            redirect('../periods.php', ['type' => 'error', 'message' => 'Invalid period ID']);
        }
        break;
        
    default:
        redirect('../periods.php');
        break;
}
?>