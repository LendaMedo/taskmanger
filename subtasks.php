<?php
/**
 * Subtasks API
 * * CRUD operations for subtasks
 * * @author Dr. Ahmed AL-sadi
 * @version 1.0
 */

// Include required files
require_once '../config.php'; // Adjust path if your config is in 'includes'
require_once '../db_connect.php'; // Adjust path if your db_connect is in 'includes'
require_once '../functions.php'; // Adjust path if your functions is in 'includes'
require_once '../auth/session.php'; // Adjust path if your session is in 'includes/auth.php'

// Ensure user is logged in
if (!isLoggedIn()) {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
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
            $majorTaskId = isset($_GET['major_task_id']) ? (int)$_GET['major_task_id'] : null;
            if ($majorTaskId === null || $majorTaskId <=0) {
                 $response = ['success' => false, 'message' => 'Major Task ID is required'];
                 break;
            }
            $sql = "SELECT * FROM SubTasks WHERE major_task_id = ? ORDER BY order_index ASC, deadline ASC, priority DESC";
            $subtasks = executeQuery($sql, [$majorTaskId]);
            $response = ['success' => true, 'data' => $subtasks];
            break;
            
        case 'get':
            $id = (int)($_POST['id'] ?? ($_GET['id'] ?? 0));
            if ($id > 0) {
                $sql = "SELECT * FROM SubTasks WHERE id = ?";
                $subtask = getRecord($sql, [$id]);
                
                if ($subtask) {
                    $response = ['success' => true, 'data' => $subtask];
                } else {
                    $response = ['success' => false, 'message' => 'Subtask not found'];
                }
            } else {
                $response = ['success' => false, 'message' => 'Invalid subtask ID'];
            }
            break;
            
        case 'create':
            $majorTaskId = (int)($_POST['major_task_id'] ?? 0);
            if ($majorTaskId <= 0) {
                 $response = ['success' => false, 'message' => 'Valid Major Task ID is required'];
                 break;
            }

            $data = [
                'major_task_id' => $majorTaskId,
                'task_name' => sanitizeInput($_POST['task_name'] ?? ''),
                'description' => sanitizeInput($_POST['description'] ?? ''),
                'priority' => sanitizeInput($_POST['priority'] ?? 'Medium'), // [cite: 13]
                'urgency' => sanitizeInput($_POST['urgency'] ?? 'Soon'), // [cite: 14]
                'importance' => sanitizeInput($_POST['importance'] ?? 'Important'), // [cite: 14]
                'deadline' => sanitizeInput($_POST['deadline'] ?? null),
                'status' => sanitizeInput($_POST['status'] ?? 'To Do'), // [cite: 14]
                'percent_complete' => min(100, max(0, (int)($_POST['percent_complete'] ?? 0))), // [cite: 14]
                'working_with' => sanitizeInput($_POST['working_with'] ?? ''),
                'notes' => sanitizeInput($_POST['notes'] ?? ''),
                'estimated_hours' => isset($_POST['estimated_hours']) ? (float)$_POST['estimated_hours'] : null, // [cite: 14]
                'actual_hours' => isset($_POST['actual_hours']) ? (float)$_POST['actual_hours'] : null, // [cite: 14]
                'assigned_to' => isset($_POST['assigned_to']) && $_POST['assigned_to'] > 0 ? (int)$_POST['assigned_to'] : null, // [cite: 14]
                'created_by' => getCurrentUserId(), // [cite: 14]
                'order_index' => (int)($_POST['order_index'] ?? 0) // [cite: 14]
            ];
            
            $errors = [];
            if (empty($data['task_name'])) {
                $errors[] = 'Subtask name is required';
            }
            
            if (empty($errors)) {
                $data['created_at'] = date('Y-m-d H:i:s'); // [cite: 15]
                $data['updated_at'] = date('Y-m-d H:i:s'); // [cite: 15]
                
                $subtaskId = insertRecord('SubTasks', $data);
                
                if ($subtaskId) {
                    logActivity('subtask_created', "Created subtask '{$data['task_name']}' for major task ID {$data['major_task_id']}");
                    // Optionally, update major task progress
                    // updateMajorTaskProgress($data['major_task_id']);
                    $response = [
                        'success' => true, 
                        'message' => 'Subtask created successfully', 
                        'id' => $subtaskId
                    ];
                } else {
                    $response = ['success' => false, 'message' => 'Failed to create subtask'];
                }
            } else {
                $response = ['success' => false, 'message' => 'Validation failed', 'errors' => $errors];
            }
            break;
            
        case 'update':
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) {
                $response = ['success' => false, 'message' => 'Invalid subtask ID'];
                break;
            }
            
            $data = [
                'task_name' => sanitizeInput($_POST['task_name'] ?? ''),
                'description' => sanitizeInput($_POST['description'] ?? ''),
                'priority' => sanitizeInput($_POST['priority'] ?? 'Medium'),
                'urgency' => sanitizeInput($_POST['urgency'] ?? 'Soon'),
                'importance' => sanitizeInput($_POST['importance'] ?? 'Important'),
                'deadline' => sanitizeInput($_POST['deadline'] ?? null),
                'status' => sanitizeInput($_POST['status'] ?? 'To Do'),
                'percent_complete' => min(100, max(0, (int)($_POST['percent_complete'] ?? 0))),
                'working_with' => sanitizeInput($_POST['working_with'] ?? ''),
                'notes' => sanitizeInput($_POST['notes'] ?? ''),
                'estimated_hours' => isset($_POST['estimated_hours']) ? (float)$_POST['estimated_hours'] : null,
                'actual_hours' => isset($_POST['actual_hours']) ? (float)$_POST['actual_hours'] : null,
                'assigned_to' => isset($_POST['assigned_to']) && $_POST['assigned_to'] > 0 ? (int)$_POST['assigned_to'] : null,
                'order_index' => (int)($_POST['order_index'] ?? 0),
                'updated_at' => date('Y-m-d H:i:s')
            ];
             // Filter out empty values to prevent overwriting with NULLs if not provided
            $updateData = array_filter($data, function($value) {
                return $value !== '' && $value !== null;
            });

            $errors = [];
            if (isset($updateData['task_name']) && empty($updateData['task_name'])) {
                 $errors[] = 'Task name cannot be empty if provided';
            }
            
            if (empty($errors) && !empty($updateData)) {
                $updated = updateRecord('SubTasks', $updateData, 'id = ?', [$id]);
                
                if ($updated) {
                    logActivity('subtask_updated', "Updated subtask ID $id");
                     // Optionally, update major task progress
                    // $subtaskDetails = getRecord("SELECT major_task_id FROM SubTasks WHERE id = ?", [$id]);
                    // if ($subtaskDetails) updateMajorTaskProgress($subtaskDetails['major_task_id']);
                    $response = ['success' => true, 'message' => 'Subtask updated successfully'];
                } else {
                    $response = ['success' => false, 'message' => 'Failed to update subtask or no changes made'];
                }
            } elseif (empty($updateData)) {
                $response = ['success' => false, 'message' => 'No data provided for update'];
            } else {
                $response = ['success' => false, 'message' => 'Validation failed', 'errors' => $errors];
            }
            break;
            
        case 'delete':
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                $subtask = getRecord("SELECT task_name, major_task_id FROM SubTasks WHERE id = ?", [$id]);
                $deleted = deleteRecord('SubTasks', 'id = ?', [$id]);
                
                if ($deleted) {
                    logActivity('subtask_deleted', "Deleted subtask '{$subtask['task_name']}' (ID $id)");
                    // Optionally, update major task progress
                    // if ($subtask) updateMajorTaskProgress($subtask['major_task_id']);
                    $response = ['success' => true, 'message' => 'Subtask deleted successfully'];
                } else {
                    $response = ['success' => false, 'message' => 'Failed to delete subtask'];
                }
            } else {
                $response = ['success' => false, 'message' => 'Invalid subtask ID'];
            }
            break;
            
        default:
            $response = ['success' => false, 'message' => 'Invalid action'];
            break;
    }
    
    echo json_encode($response);
    exit;
}

// Placeholder for function to update major task progress based on its subtasks
// function updateMajorTaskProgress($majorTaskId) {
//     // Implementation needed:
//     // 1. Get all subtasks for the major_task_id
//     // 2. Calculate the overall progress (e.g., average of percent_complete or based on completed subtasks)
//     // 3. Update the MajorTasks table for the given major_task_id
//     // This might involve calling calculateTaskCompletion($majorTaskId) from functions.php
//     // and then updating the MajorTasks table.
// }

// Non-AJAX requests (e.g., for rendering forms directly if needed, though typically handled by frontend)
// This part can be expanded if you intend to render HTML forms directly from these API files.
// For now, it's primarily designed for AJAX.
?>