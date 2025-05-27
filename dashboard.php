<?php
/**
 * MyWorkHub - Dashboard Page (dashboard.php)
 *
 * Displays an overview of work periods, tasks, and progress.
 * Allows users to manage their work.
 *
 * @author Dr. Ahmed AL-sadi
 * @version 2.0 (Functional with API integration)
 */

// Define ROOT_PATH for consistent includes. Assumes dashboard.php is in the 'workhub' directory.
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__);
}

// Include necessary files
require_once ROOT_PATH . '/includes/config.php';
require_once ROOT_PATH . '/auth/session.php';   // Handles session initialization, isLoggedIn(), getCurrentUserId(), etc.
                                                // session.php should include functions.php which includes db.php

// Check if the user is logged in
if (!isLoggedIn()) {
    redirect('login.php', ['type' => 'error', 'message' => 'Please log in to view the dashboard.']);
    exit;
}

// Get current user's information for display (optional, if needed for personalization)
$currentUserId = getCurrentUserId();
$username = $_SESSION['username'] ?? 'User'; // Get username from session

$pageTitle = "Dashboard - " . SITE_NAME;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .period-card {
            transition: all 0.3s ease;
        }
        .period-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .task-row:hover {
            background-color: #f9fafb; /* Slightly lighter gray for hover */
        }
        .progress-container {
            height: 8px;
            background-color: #e2e8f0; /* coolGray-200 */
            border-radius: 4px;
            overflow: hidden; /* Ensures progress bar stays within rounded corners */
        }
        .progress-bar {
            height: 8px;
            border-radius: 4px; /* Should match container if you want smooth ends */
            transition: width 0.5s ease-in-out;
        }
        .modal {
            transition: opacity 0.3s ease;
        }
        .modal-content {
            max-height: 90vh;
            overflow-y: auto;
        }
        /* Custom spinner for buttons */
        .spinner-button {
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: #ffffff;
            border-radius: 50%;
            width: 1rem; /* 16px */
            height: 1rem; /* 16px */
            animation: spin 1s linear infinite;
            display: inline-block;
            margin-right: 0.5rem; /* 8px */
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .no-print { /* Class to hide elements during printing */
             display: revert; /* Default display */
        }
        @media print {
            body * {
                visibility: hidden;
            }
            .printable-area, .printable-area * {
                visibility: visible;
            }
            .printable-area {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }
            .no-print {
                display: none !important;
            }
            .page-break {
                page-break-before: always;
            }
            header, footer, nav, aside { /* Common elements to hide */
                display: none !important;
            }
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <header class="bg-white shadow-md no-print">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center">
                    <a href="dashboard.php" class="flex-shrink-0">
                        <i class="fas fa-tasks text-3xl text-indigo-600"></i>
                        <span class="ml-2 text-2xl font-bold text-indigo-600"><?php echo htmlspecialchars(SITE_NAME); ?></span>
                    </a>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-600 hidden sm:block"><i class="fas fa-user-circle mr-2 text-indigo-600"></i>Welcome, <?php echo htmlspecialchars($username); ?>!</span>
                    <a href="profile.php" class="text-gray-600 hover:text-indigo-600 px-3 py-2 rounded-md text-sm font-medium" title="My Profile">
                        <i class="fas fa-cog"></i> <span class="hidden sm:inline">Profile</span>
                    </a>
                    <button id="logout-btn" class="bg-red-500 hover:bg-red-600 text-white px-3 py-2 rounded-md text-sm font-medium flex items-center">
                        <i class="fas fa-sign-out-alt mr-1"></i> Logout
                    </button>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 printable-area">
        <div id="global-alert" class="mb-4 p-4 rounded-md text-sm hidden no-print"></div>

        <section id="dashboard-summary" class="mb-10">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-semibold text-gray-800">Dashboard Overview</h2>
                <p class="text-sm text-gray-500 no-print">Last updated: <span id="last-updated-time"><?php echo date("M j, Y g:i A"); ?></span></p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-white rounded-lg shadow-lg p-6 flex items-center">
                    <div class="rounded-full bg-blue-100 p-4 mr-4"> <i class="fas fa-calendar-alt text-blue-600 text-2xl"></i> </div>
                    <div> <p class="text-sm text-gray-500">Active Periods</p> <p id="summary-active-periods" class="text-3xl font-bold text-gray-800">0</p> </div>
                </div>
                <div class="bg-white rounded-lg shadow-lg p-6 flex items-center">
                    <div class="rounded-full bg-green-100 p-4 mr-4"> <i class="fas fa-tasks text-green-600 text-2xl"></i> </div>
                    <div> <p class="text-sm text-gray-500">Total Major Tasks</p> <p id="summary-major-tasks" class="text-3xl font-bold text-gray-800">0</p> </div>
                </div>
                <div class="bg-white rounded-lg shadow-lg p-6 flex items-center">
                    <div class="rounded-full bg-yellow-100 p-4 mr-4"> <i class="fas fa-list-ul text-yellow-600 text-2xl"></i> </div>
                    <div> <p class="text-sm text-gray-500">Total Subtasks</p> <p id="summary-subtasks" class="text-3xl font-bold text-gray-800">0</p> </div>
                </div>
                <div class="bg-white rounded-lg shadow-lg p-6 flex items-center">
                    <div class="rounded-full bg-purple-100 p-4 mr-4"> <i class="fas fa-check-circle text-purple-600 text-2xl"></i> </div>
                    <div> <p class="text-sm text-gray-500">Overall Completion</p> <p id="summary-overall-completion" class="text-3xl font-bold text-gray-800">0%</p> </div>
                </div>
            </div>
        </section>
        
        <section class="mb-10 bg-white rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Task Progress Overview (by Period)</h2>
            <div class="w-full h-72 md:h-96">
                <canvas id="progressChart"></canvas>
            </div>
        </section>

        <section id="periods-section" class="mb-10">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-semibold text-gray-800">Work Periods</h2>
                <button id="add-period-btn" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium flex items-center no-print">
                    <i class="fas fa-plus mr-2"></i> Add Period
                </button>
            </div>
            <div id="periods-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <p id="no-periods-message" class="text-gray-500 col-span-full hidden">No work periods found. Get started by adding one!</p>
            </div>
        </section>

        <section id="major-tasks-section" class="mb-10">
            <div class="flex flex-wrap justify-between items-center mb-6 gap-4">
                <h2 class="text-2xl font-semibold text-gray-800">Major Tasks</h2>
                <div class="flex items-center space-x-2 no-print">
                    <select id="period-filter" class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="all">All Periods</option>
                        </select>
                    <button id="add-task-btn" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium flex items-center">
                        <i class="fas fa-plus mr-2"></i> Add Task
                    </button>
                </div>
            </div>
            <div class="bg-white shadow-lg rounded-lg overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Task Name</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Period</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deadline</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Progress</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider no-print">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="major-tasks-tbody" class="bg-white divide-y divide-gray-200">
                        <tr id="no-major-tasks-row" class="hidden"><td colspan="7" class="text-center py-4 text-gray-500">No major tasks found for the selected period.</td></tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section id="subtasks-display-section" class="mb-10 hidden">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-semibold text-gray-800">Subtasks for: <span id="current-major-task-name" class="text-indigo-600"></span></h2>
                <div class="space-x-2 no-print">
                     <button id="add-subtask-btn" data-major-task-id="" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium flex items-center">
                        <i class="fas fa-plus mr-2"></i> Add Subtask
                    </button>
                    <button id="hide-subtasks-btn" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-chevron-up mr-2"></i> Hide Subtasks
                    </button>
                </div>
            </div>
            <div class="bg-white shadow-lg rounded-lg overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subtask Name</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deadline</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Progress</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider no-print">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="subtasks-tbody" class="bg-white divide-y divide-gray-200">
                        <tr id="no-subtasks-row" class="hidden"><td colspan="6" class="text-center py-4 text-gray-500">No subtasks found for this major task.</td></tr>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <footer class="bg-white border-t border-gray-200 mt-12 py-6 no-print">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <p class="text-sm text-gray-500">&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars(SITE_NAME); ?>. Created by Dr. Ahmed AL-sadi. All rights reserved.</p>
        </div>
    </footer>

    <div id="period-modal" class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 hidden p-4 no-print">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full modal-content">
            <div class="bg-gray-100 py-3 px-4 rounded-t-lg flex justify-between items-center">
                <h3 id="period-modal-title" class="text-lg font-semibold text-gray-800">Add New Period</h3>
                <button class="close-modal-btn text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
            </div>
            <div class="p-6">
                <form id="period-form" class="space-y-4">
                    <input type="hidden" id="period-id" name="id">
                    <div>
                        <label for="period-name" class="block text-sm font-medium text-gray-700">Period Name <span class="text-red-500">*</span></label>
                        <input type="text" id="period-name" name="name" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    </div>
                    <div>
                        <label for="period-description" class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea id="period-description" name="description" rows="3" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"></textarea>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="period-start-date" class="block text-sm font-medium text-gray-700">Start Date</label>
                            <input type="date" id="period-start-date" name="start_date" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <label for="period-end-date" class="block text-sm font-medium text-gray-700">End Date</label>
                            <input type="date" id="period-end-date" name="end_date" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>
                    </div>
                     <div>
                        <label for="period-status" class="block text-sm font-medium text-gray-700">Status</label>
                        <select id="period-status" name="status" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            <option value="active">Active</option>
                            <option value="planned">Planned</option>
                            <option value="completed">Completed</option>
                            <option value="archived">Archived</option>
                        </select>
                    </div>
                    <div class="flex justify-end pt-4 space-x-3">
                        <button type="button" class="close-modal-btn bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md py-2 px-4 text-sm font-medium">Cancel</button>
                        <button type="submit" id="save-period-btn" class="bg-indigo-600 hover:bg-indigo-700 text-white rounded-md py-2 px-4 text-sm font-medium flex items-center justify-center">
                            <span class="spinner-button hidden"></span> Save Period
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="task-modal" class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 hidden p-4 no-print">
        <div class="bg-white rounded-lg shadow-xl max-w-lg w-full modal-content">
            <div class="bg-gray-100 py-3 px-4 rounded-t-lg flex justify-between items-center">
                <h3 id="task-modal-title" class="text-lg font-semibold text-gray-800">Add New Major Task</h3>
                <button class="close-modal-btn text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
            </div>
            <div class="p-6">
                <form id="task-form" class="space-y-4">
                    <input type="hidden" id="task-id" name="id">
                    <div>
                        <label for="task-name" class="block text-sm font-medium text-gray-700">Task Name <span class="text-red-500">*</span></label>
                        <input type="text" id="task-name" name="task_name" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    </div>
                    <div>
                        <label for="task-description" class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea id="task-description" name="description" rows="3" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"></textarea>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="task-period-id" class="block text-sm font-medium text-gray-700">Period</label>
                            <select id="task-period-id" name="period_id" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <option value="">Select Period</option>
                                </select>
                        </div>
                        <div>
                            <label for="task-deadline" class="block text-sm font-medium text-gray-700">Deadline</label>
                            <input type="date" id="task-deadline" name="deadline" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="task-priority" class="block text-sm font-medium text-gray-700">Priority</label>
                            <select id="task-priority" name="priority" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <option value="Low">Low</option>
                                <option value="Medium" selected>Medium</option>
                                <option value="High">High</option>
                                <option value="Critical">Critical</option>
                            </select>
                        </div>
                        <div>
                            <label for="task-status" class="block text-sm font-medium text-gray-700">Status</label>
                            <select id="task-status" name="status" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <option value="To Do">To Do</option>
                                <option value="In Progress">In Progress</option>
                                <option value="Completed">Completed</option>
                                <option value="On Hold">On Hold</option>
                                <option value="Cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>
                     <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="task-urgency" class="block text-sm font-medium text-gray-700">Urgency</label>
                            <select id="task-urgency" name="urgency" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <option value="Flexible">Flexible</option>
                                <option value="Soon" selected>Soon</option>
                                <option value="Immediate">Immediate</option>
                                <option value="Daily">Daily</option>
                                <option value="Weekly">Weekly</option>
                                <option value="Periodic">Periodic</option>
                            </select>
                        </div>
                        <div>
                            <label for="task-importance" class="block text-sm font-medium text-gray-700">Importance</label>
                            <select id="task-importance" name="importance" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <option value="Routine">Routine</option>
                                <option value="Important" selected>Important</option>
                                <option value="Critical">Critical</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label for="task-working-with" class="block text-sm font-medium text-gray-700">Working With</label>
                        <input type="text" id="task-working-with" name="working_with" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    </div>
                    <div>
                        <label for="task-percent-complete" class="block text-sm font-medium text-gray-700">% Complete</label>
                        <div class="flex items-center mt-1">
                            <input type="range" id="task-percent-complete" name="percent_complete" min="0" max="100" value="0" class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer">
                            <span id="task-percent-value" class="ml-3 text-sm text-gray-600 w-10 text-right">0%</span>
                        </div>
                    </div>
                    <div>
                        <label for="task-notes" class="block text-sm font-medium text-gray-700">Notes</label>
                        <textarea id="task-notes" name="notes" rows="3" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"></textarea>
                    </div>
                    <div class="flex justify-end pt-4 space-x-3">
                         <button type="button" class="close-modal-btn bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md py-2 px-4 text-sm font-medium">Cancel</button>
                        <button type="submit" id="save-task-btn" class="bg-indigo-600 hover:bg-indigo-700 text-white rounded-md py-2 px-4 text-sm font-medium flex items-center justify-center">
                             <span class="spinner-button hidden"></span> Save Task
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="subtask-modal" class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 hidden p-4 no-print">
        <div class="bg-white rounded-lg shadow-xl max-w-lg w-full modal-content">
            <div class="bg-gray-100 py-3 px-4 rounded-t-lg flex justify-between items-center">
                <h3 id="subtask-modal-title" class="text-lg font-semibold text-gray-800">Add New Subtask</h3>
                <button class="close-modal-btn text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
            </div>
            <div class="p-6">
                <form id="subtask-form" class="space-y-4">
                    <input type="hidden" id="subtask-id" name="id">
                    <input type="hidden" id="subtask-major-task-id" name="major_task_id">
                    <div>
                        <label for="subtask-name" class="block text-sm font-medium text-gray-700">Subtask Name <span class="text-red-500">*</span></label>
                        <input type="text" id="subtask-name" name="task_name" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    </div>
                    <div>
                        <label for="subtask-description" class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea id="subtask-description" name="description" rows="3" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"></textarea>
                    </div>
                     <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="subtask-priority" class="block text-sm font-medium text-gray-700">Priority</label>
                            <select id="subtask-priority" name="priority" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <option value="Low">Low</option>
                                <option value="Medium" selected>Medium</option>
                                <option value="High">High</option>
                                <option value="Critical">Critical</option>
                            </select>
                        </div>
                        <div>
                            <label for="subtask-deadline" class="block text-sm font-medium text-gray-700">Deadline</label>
                            <input type="date" id="subtask-deadline" name="deadline" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="subtask-status" class="block text-sm font-medium text-gray-700">Status</label>
                            <select id="subtask-status" name="status" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <option value="To Do">To Do</option>
                                <option value="In Progress">In Progress</option>
                                <option value="Completed">Completed</option>
                                <option value="On Hold">On Hold</option>
                                <option value="Cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div>
                            <label for="subtask-urgency" class="block text-sm font-medium text-gray-700">Urgency</label>
                            <select id="subtask-urgency" name="urgency" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <option value="Flexible">Flexible</option>
                                <option value="Soon" selected>Soon</option>
                                <option value="Immediate">Immediate</option>
                                <option value="Daily">Daily</option>
                                <option value="Weekly">Weekly</option>
                                <option value="Periodic">Periodic</option>
                            </select>
                        </div>
                    </div>
                     <div>
                        <label for="subtask-importance" class="block text-sm font-medium text-gray-700">Importance</label>
                        <select id="subtask-importance" name="importance" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            <option value="Routine">Routine</option>
                            <option value="Important" selected>Important</option>
                            <option value="Critical">Critical</option>
                        </select>
                    </div>
                    <div>
                        <label for="subtask-working-with" class="block text-sm font-medium text-gray-700">Working With</label>
                        <input type="text" id="subtask-working-with" name="working_with" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    </div>
                    <div>
                        <label for="subtask-percent-complete" class="block text-sm font-medium text-gray-700">% Complete</label>
                         <div class="flex items-center mt-1">
                            <input type="range" id="subtask-percent-complete" name="percent_complete" min="0" max="100" value="0" class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer">
                            <span id="subtask-percent-value" class="ml-3 text-sm text-gray-600 w-10 text-right">0%</span>
                        </div>
                    </div>
                    <div>
                        <label for="subtask-notes" class="block text-sm font-medium text-gray-700">Notes</label>
                        <textarea id="subtask-notes" name="notes" rows="3" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"></textarea>
                    </div>
                    <div class="flex justify-end pt-4 space-x-3">
                        <button type="button" class="close-modal-btn bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md py-2 px-4 text-sm font-medium">Cancel</button>
                        <button type="submit" id="save-subtask-btn" class="bg-indigo-600 hover:bg-indigo-700 text-white rounded-md py-2 px-4 text-sm font-medium flex items-center justify-center">
                            <span class="spinner-button hidden"></span> Save Subtask
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="delete-confirm-modal" class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 hidden p-4 no-print">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
            <div class="bg-red-100 py-3 px-4 rounded-t-lg flex justify-between items-center">
                <h3 class="text-lg font-semibold text-red-800">Confirm Deletion</h3>
                <button class="close-modal-btn text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
            </div>
            <div class="p-6">
                <p id="delete-confirm-message" class="text-gray-700 mb-6">Are you sure you want to delete this item? This action cannot be undone.</p>
                <div class="flex justify-end space-x-3">
                    <button type="button" class="close-modal-btn bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md py-2 px-4 text-sm font-medium">Cancel</button>
                    <button type="button" id="confirm-delete-btn" class="bg-red-600 hover:bg-red-700 text-white rounded-md py-2 px-4 text-sm font-medium flex items-center justify-center">
                         <span class="spinner-button hidden"></span> Delete
                    </button>
                </div>
            </div>
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- Configuration ---
    const API_BASE_URL = 'api/'; // Adjust if your API folder is elsewhere

    // --- Global State ---
    let allPeriods = [];
    let allMajorTasks = [];
    let currentDeleteItem = null; // { type: 'period'/'task'/'subtask', id: itemId }
    let progressChartInstance = null;

    // --- DOM Elements ---
    const periodsContainer = document.getElementById('periods-container');
    const noPeriodsMessage = document.getElementById('no-periods-message');
    const majorTasksTbody = document.getElementById('major-tasks-tbody');
    const noMajorTasksRow = document.getElementById('no-major-tasks-row');
    const subtasksTbody = document.getElementById('subtasks-tbody');
    const noSubtasksRow = document.getElementById('no-subtasks-row');
    const periodFilterSelect = document.getElementById('period-filter');
    const taskPeriodSelect = document.getElementById('task-period-id'); // In task modal
    const subtaskMajorTaskIdInput = document.getElementById('subtask-major-task-id');

    const periodModal = document.getElementById('period-modal');
    const periodForm = document.getElementById('period-form');
    const periodModalTitle = document.getElementById('period-modal-title');
    const savePeriodBtn = document.getElementById('save-period-btn');

    const taskModal = document.getElementById('task-modal');
    const taskForm = document.getElementById('task-form');
    const taskModalTitle = document.getElementById('task-modal-title');
    const saveTaskBtn = document.getElementById('save-task-btn');
    const taskPercentCompleteSlider = document.getElementById('task-percent-complete');
    const taskPercentValueDisplay = document.getElementById('task-percent-value');

    const subtaskModal = document.getElementById('subtask-modal');
    const subtaskForm = document.getElementById('subtask-form');
    const subtaskModalTitle = document.getElementById('subtask-modal-title');
    const saveSubtaskBtn = document.getElementById('save-subtask-btn');
    const addSubtaskBtnGlobal = document.getElementById('add-subtask-btn'); // Button in subtasks section header
    const subtaskPercentCompleteSlider = document.getElementById('subtask-percent-complete');
    const subtaskPercentValueDisplay = document.getElementById('subtask-percent-value');


    const subtasksDisplaySection = document.getElementById('subtasks-display-section');
    const currentMajorTaskNameSpan = document.getElementById('current-major-task-name');

    const deleteConfirmModal = document.getElementById('delete-confirm-modal');
    const confirmDeleteBtn = document.getElementById('confirm-delete-btn');
    const deleteConfirmMessage = document.getElementById('delete-confirm-message');

    const globalAlert = document.getElementById('global-alert');

    // --- Utility Functions ---
    function showGlobalAlert(message, type = 'error') {
        globalAlert.textContent = message;
        globalAlert.classList.remove('hidden', 'bg-red-100', 'text-red-700', 'bg-green-100', 'text-green-700', 'bg-blue-100', 'text-blue-700');
        if (type === 'error') {
            globalAlert.classList.add('bg-red-100', 'text-red-700');
        } else if (type === 'success') {
            globalAlert.classList.add('bg-green-100', 'text-green-700');
        } else { // info
            globalAlert.classList.add('bg-blue-100', 'text-blue-700');
        }
        globalAlert.classList.remove('hidden');
        setTimeout(() => globalAlert.classList.add('hidden'), 5000); // Auto-hide after 5 seconds
    }

    function openModal(modalElement) {
        modalElement.classList.remove('hidden');
    }

    function closeModal(modalElement) {
        modalElement.classList.add('hidden');
        // Reset forms within the modal if any
        const form = modalElement.querySelector('form');
        if (form) form.reset();
        // Reset specific fields like hidden IDs
        if (modalElement.id === 'period-modal') document.getElementById('period-id').value = '';
        if (modalElement.id === 'task-modal') document.getElementById('task-id').value = '';
        if (modalElement.id === 'subtask-modal') document.getElementById('subtask-id').value = '';
    }

    function formatDateForInput(dateString) {
        if (!dateString || dateString === '0000-00-00') return '';
        try {
            const date = new Date(dateString);
            // Adjust for timezone offset to get correct YYYY-MM-DD
            date.setMinutes(date.getMinutes() - date.getTimezoneOffset());
            return date.toISOString().split('T')[0];
        } catch (e) {
            return '';
        }
    }
    
    function getStatusColor(status) {
        switch (status) {
            case 'Completed': return 'bg-green-500';
            case 'In Progress': return 'bg-blue-500';
            case 'On Hold': return 'bg-yellow-500';
            case 'Cancelled': return 'bg-red-500';
            case 'To Do': default: return 'bg-gray-300';
        }
    }

    function getPriorityClasses(priority) {
        switch (priority) {
            case 'Critical': return 'bg-red-600 text-white';
            case 'High': return 'bg-red-200 text-red-800';
            case 'Medium': return 'bg-yellow-200 text-yellow-800';
            case 'Low': return 'bg-green-200 text-green-800';
            default: return 'bg-gray-200 text-gray-800';
        }
    }
    
    function showButtonSpinner(button, show = true) {
        const spinner = button.querySelector('.spinner-button');
        const textNode = Array.from(button.childNodes).find(node => node.nodeType === Node.TEXT_NODE);
        if (spinner) spinner.classList.toggle('hidden', !show);
        if (textNode) textNode.textContent = show ? ' Processing...' : button.dataset.originalText || textNode.textContent;
        button.disabled = show;
    }


    // --- API Call Functions ---
    async function fetchData(endpoint, options = {}) {
        showGlobalAlert('Loading data...', 'info');
        try {
            const response = await fetch(API_BASE_URL + endpoint, options);
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({ message: `HTTP error! Status: ${response.status}` }));
                throw new Error(errorData.message || `HTTP error! Status: ${response.status}`);
            }
            const data = await response.json();
            if (!data.success) {
                throw new Error(data.message || 'API request failed');
            }
            globalAlert.classList.add('hidden'); // Hide loading message
            return data.data;
        } catch (error) {
            console.error(`Error fetching ${endpoint}:`, error);
            showGlobalAlert(`Failed to load data from ${endpoint}: ${error.message}`, 'error');
            throw error; // Re-throw to allow caller to handle
        }
    }

    async function postData(endpoint, formData, action) {
        formData.append('action', action);
        try {
            const response = await fetch(API_BASE_URL + endpoint, {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            if (!result.success) {
                let errorMessage = result.message || `Failed to ${action} item.`;
                if (result.errors) {
                    errorMessage += ' Details: ' + result.errors.join(', ');
                }
                throw new Error(errorMessage);
            }
            return result;
        } catch (error) {
            console.error(`Error in ${action} for ${endpoint}:`, error);
            showGlobalAlert(error.message, 'error');
            throw error;
        }
    }

    // --- Rendering Functions ---
    function renderPeriods(periods) {
        periodsContainer.innerHTML = ''; // Clear existing
        periodFilterSelect.innerHTML = '<option value="all">All Periods</option>'; // Reset filter
        taskPeriodSelect.innerHTML = '<option value="">Select Period</option>'; // Reset task modal dropdown

        if (periods && periods.length > 0) {
            noPeriodsMessage.classList.add('hidden');
            periods.forEach(period => {
                const card = `
                    <div class="period-card bg-white rounded-lg shadow-md overflow-hidden flex flex-col" data-period-id="${period.id}">
                        <div class="p-5 flex-grow">
                            <h3 class="font-bold text-lg text-indigo-700 mb-1">${htmlspecialchars(period.name)}</h3>
                            <p class="text-gray-500 text-xs mb-2">
                                ${period.start_date ? formatDateForInput(period.start_date) : 'N/A'} - 
                                ${period.end_date ? formatDateForInput(period.end_date) : 'N/A'}
                            </p>
                            <p class="text-gray-600 text-sm mb-3 h-10 overflow-hidden">${htmlspecialchars(period.description || 'No description')}</p>
                             <div class="text-xs text-gray-500 mb-1">Status: <span class="font-semibold ${period.status === 'active' ? 'text-green-600' : 'text-gray-600'}">${htmlspecialchars(period.status)}</span></div>
                            </div>
                        <div class="bg-gray-50 px-5 py-3 flex justify-end space-x-2 no-print">
                            <button class="edit-period-btn text-blue-600 hover:text-blue-800 text-sm" data-id="${period.id}"><i class="fas fa-edit mr-1"></i> Edit</button>
                            <button class="delete-item-btn text-red-600 hover:text-red-800 text-sm" data-id="${period.id}" data-type="period" data-name="${htmlspecialchars(period.name)}"><i class="fas fa-trash-alt mr-1"></i> Delete</button>
                        </div>
                    </div>`;
                periodsContainer.insertAdjacentHTML('beforeend', card);
                // Populate filter and modal dropdowns
                periodFilterSelect.add(new Option(period.name, period.id));
                taskPeriodSelect.add(new Option(period.name, period.id));
            });
        } else {
            noPeriodsMessage.classList.remove('hidden');
        }
        allPeriods = periods || [];
        updateSummaryCards();
        updateProgressChart();
    }

    function renderMajorTasks(tasks) {
        majorTasksTbody.innerHTML = ''; // Clear existing
        if (tasks && tasks.length > 0) {
            noMajorTasksRow.classList.add('hidden');
            tasks.forEach(task => {
                const periodName = allPeriods.find(p => p.id == task.period_id)?.name || 'N/A';
                const priorityClasses = getPriorityClasses(task.priority);
                const row = `
                    <tr class="task-row" data-task-id="${task.id}">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">${htmlspecialchars(task.task_name)}</div>
                            <div class="text-xs text-gray-500">${htmlspecialchars(task.working_with || 'Solo')}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${htmlspecialchars(periodName)}</td>
                        <td class="px-6 py-4 whitespace-nowrap"><span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full ${priorityClasses}">${htmlspecialchars(task.priority)}</span></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${task.deadline ? formatDateForInput(task.deadline) : 'N/A'}</td>
                        <td class="px-6 py-4 whitespace-nowrap"><span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full ${getStatusBadgeClasses(task.status)}">${htmlspecialchars(task.status)}</span></td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-xs text-gray-500 mb-1">${task.percent_complete || 0}%</div>
                            <div class="progress-container w-24 sm:w-32"><div class="progress-bar ${getStatusColor(task.status)}" style="width: ${task.percent_complete || 0}%"></div></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-1 no-print">
                            <button class="view-subtasks-btn text-green-600 hover:text-green-800" data-id="${task.id}" data-name="${htmlspecialchars(task.task_name)}" title="View Subtasks"><i class="fas fa-list-ul"></i></button>
                            <button class="edit-task-btn text-blue-600 hover:text-blue-800" data-id="${task.id}" title="Edit Task"><i class="fas fa-edit"></i></button>
                            <button class="delete-item-btn text-red-600 hover:text-red-800" data-id="${task.id}" data-type="task" data-name="${htmlspecialchars(task.task_name)}" title="Delete Task"><i class="fas fa-trash-alt"></i></button>
                        </td>
                    </tr>`;
                majorTasksTbody.insertAdjacentHTML('beforeend', row);
            });
        } else {
            noMajorTasksRow.classList.remove('hidden');
        }
        allMajorTasks = tasks || [];
        updateSummaryCards();
        updateProgressChart();
    }
    
    function getStatusBadgeClasses(status) {
        switch (status) {
            case 'Completed': return 'bg-green-100 text-green-800';
            case 'In Progress': return 'bg-blue-100 text-blue-800';
            case 'On Hold': return 'bg-yellow-100 text-yellow-800';
            case 'Cancelled': return 'bg-red-100 text-red-800';
            case 'To Do': default: return 'bg-gray-100 text-gray-800';
        }
    }

    function renderSubtasks(subtasks) {
        subtasksTbody.innerHTML = ''; // Clear existing
        if (subtasks && subtasks.length > 0) {
            noSubtasksRow.classList.add('hidden');
            subtasks.forEach(subtask => {
                const priorityClasses = getPriorityClasses(subtask.priority);
                const row = `
                    <tr class="task-row" data-subtask-id="${subtask.id}">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">${htmlspecialchars(subtask.task_name)}</div>
                             <div class="text-xs text-gray-500">${htmlspecialchars(subtask.working_with || 'Solo')}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap"><span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full ${priorityClasses}">${htmlspecialchars(subtask.priority)}</span></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${subtask.deadline ? formatDateForInput(subtask.deadline) : 'N/A'}</td>
                        <td class="px-6 py-4 whitespace-nowrap"><span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full ${getStatusBadgeClasses(subtask.status)}">${htmlspecialchars(subtask.status)}</span></td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-xs text-gray-500 mb-1">${subtask.percent_complete || 0}%</div>
                            <div class="progress-container w-24 sm:w-32"><div class="progress-bar ${getStatusColor(subtask.status)}" style="width: ${subtask.percent_complete || 0}%"></div></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-1 no-print">
                            <button class="edit-subtask-btn text-blue-600 hover:text-blue-800" data-id="${subtask.id}" title="Edit Subtask"><i class="fas fa-edit"></i></button>
                            <button class="delete-item-btn text-red-600 hover:text-red-800" data-id="${subtask.id}" data-type="subtask" data-name="${htmlspecialchars(subtask.task_name)}" title="Delete Subtask"><i class="fas fa-trash-alt"></i></button>
                        </td>
                    </tr>`;
                subtasksTbody.insertAdjacentHTML('beforeend', row);
            });
        } else {
            noSubtasksRow.classList.remove('hidden');
        }
    }

    function htmlspecialchars(str) {
        if (typeof str !== 'string') return '';
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return str.replace(/[&<>"']/g, m => map[m]);
    }
    
    function updateSummaryCards() {
        document.getElementById('summary-active-periods').textContent = allPeriods.filter(p => p.status === 'active').length;
        document.getElementById('summary-major-tasks').textContent = allMajorTasks.length;
        
        // For subtasks and overall completion, we'd need to fetch all subtasks or calculate more complexly
        // For now, let's assume these are placeholders or would be updated by more specific logic
        // Example: Calculate overall completion based on major tasks
        if (allMajorTasks.length > 0) {
            const totalPercent = allMajorTasks.reduce((sum, task) => sum + (parseInt(task.percent_complete) || 0), 0);
            document.getElementById('summary-overall-completion').textContent = Math.round(totalPercent / allMajorTasks.length) + '%';
        } else {
            document.getElementById('summary-overall-completion').textContent = '0%';
        }
        // Subtask count would require fetching all subtasks.
        // For simplicity, this might be fetched separately or if tasks data includes subtask counts.
    }

    function updateProgressChart() {
        const activePeriods = allPeriods.filter(p => p.status === 'active' || p.status === 'planned');
        const labels = activePeriods.map(p => p.name);
        const data = activePeriods.map(period => {
            const tasksInPeriod = allMajorTasks.filter(t => t.period_id == period.id);
            if (tasksInPeriod.length === 0) return 0;
            const totalPercent = tasksInPeriod.reduce((sum, task) => sum + (parseInt(task.percent_complete) || 0), 0);
            return Math.round(totalPercent / tasksInPeriod.length);
        });

        if (progressChartInstance) {
            progressChartInstance.data.labels = labels;
            progressChartInstance.data.datasets[0].data = data;
            progressChartInstance.update();
        } else {
            const ctx = document.getElementById('progressChart').getContext('2d');
            progressChartInstance = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Avg. Task Progress (%)',
                        data: data,
                        backgroundColor: 'rgba(79, 70, 229, 0.6)', // Indigo-600
                        borderColor: 'rgba(79, 70, 229, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: { y: { beginAtZero: true, max: 100, ticks: { callback: value => value + '%' } } },
                    plugins: { tooltip: { callbacks: { label: context => context.dataset.label + ': ' + context.raw + '%' } } }
                }
            });
        }
    }


    // --- Event Handlers ---
    // Modal open/close
    document.getElementById('add-period-btn').addEventListener('click', () => {
        periodModalTitle.textContent = 'Add New Period';
        periodForm.reset(); // Ensure form is clean
        document.getElementById('period-id').value = ''; // Clear ID for new entry
        openModal(periodModal);
    });
    document.getElementById('add-task-btn').addEventListener('click', () => {
        taskModalTitle.textContent = 'Add New Major Task';
        taskForm.reset();
        document.getElementById('task-id').value = '';
        taskPercentValueDisplay.textContent = '0%'; // Reset slider display
        openModal(taskModal);
    });
     // Add Subtask button in the subtasks section header
    addSubtaskBtnGlobal.addEventListener('click', () => {
        const majorTaskId = addSubtaskBtnGlobal.dataset.majorTaskId;
        if (!majorTaskId) {
            showGlobalAlert('Cannot add subtask: No major task selected.', 'error');
            return;
        }
        subtaskModalTitle.textContent = 'Add New Subtask';
        subtaskForm.reset();
        document.getElementById('subtask-id').value = '';
        subtaskMajorTaskIdInput.value = majorTaskId; // Set the major_task_id for the new subtask
        subtaskPercentValueDisplay.textContent = '0%';
        openModal(subtaskModal);
    });

    document.querySelectorAll('.close-modal-btn').forEach(btn => {
        btn.addEventListener('click', () => closeModal(btn.closest('.fixed.inset-0')));
    });

    // Form Submissions
    periodForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const periodId = formData.get('id');
        const action = periodId ? 'update' : 'create';
        const saveBtn = document.getElementById('save-period-btn');
        saveBtn.dataset.originalText = saveBtn.textContent; // Store original text
        showButtonSpinner(saveBtn, true);

        try {
            const result = await postData('periods.php', formData, action);
            showGlobalAlert(result.message || `Period ${action}d successfully!`, 'success');
            closeModal(periodModal);
            loadAllData(); // Refresh data
        } catch (error) {
            // Error already shown by postData
        } finally {
            showButtonSpinner(saveBtn, false);
        }
    });

    taskForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const taskId = formData.get('id');
        const action = taskId ? 'update' : 'create';
        const saveBtn = document.getElementById('save-task-btn');
        saveBtn.dataset.originalText = saveBtn.textContent;
        showButtonSpinner(saveBtn, true);
        
        try {
            const result = await postData('tasks.php', formData, action);
            showGlobalAlert(result.message || `Task ${action}d successfully!`, 'success');
            closeModal(taskModal);
            loadAllData(); // Refresh data
        } catch (error) {
            // Error already shown
        } finally {
            showButtonSpinner(saveBtn, false);
        }
    });
    
    // Percent complete slider for Task Modal
    if (taskPercentCompleteSlider && taskPercentValueDisplay) {
        taskPercentCompleteSlider.addEventListener('input', function() {
            taskPercentValueDisplay.textContent = this.value + '%';
        });
    }
    // Percent complete slider for Subtask Modal
    if (subtaskPercentCompleteSlider && subtaskPercentValueDisplay) {
        subtaskPercentCompleteSlider.addEventListener('input', function() {
            subtaskPercentValueDisplay.textContent = this.value + '%';
        });
    }


    subtaskForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const subtaskId = formData.get('id');
        const action = subtaskId ? 'update' : 'create';
        const majorTaskId = formData.get('major_task_id'); // Ensure this is set
        const saveBtn = document.getElementById('save-subtask-btn');
        saveBtn.dataset.originalText = saveBtn.textContent;
        showButtonSpinner(saveBtn, true);

        if (!majorTaskId) {
            showGlobalAlert('Major Task ID is missing. Cannot save subtask.', 'error');
            showButtonSpinner(saveBtn, false);
            return;
        }
        
        try {
            const result = await postData('subtasks.php', formData, action);
            showGlobalAlert(result.message || `Subtask ${action}d successfully!`, 'success');
            closeModal(subtaskModal);
            // Refresh subtasks for the current major task
            if (subtasksDisplaySection.style.display !== 'none' && addSubtaskBtnGlobal.dataset.majorTaskId) {
                 loadSubtasksForMajorTask(addSubtaskBtnGlobal.dataset.majorTaskId, currentMajorTaskNameSpan.textContent);
            }
            loadAllData(); // Also refresh overall data for summary/chart
        } catch (error) {
            // Error already shown
        } finally {
            showButtonSpinner(saveBtn, false);
        }
    });

    // Edit and Delete buttons (event delegation)
    document.body.addEventListener('click', async function(e) {
        // Edit Period
        if (e.target.closest('.edit-period-btn')) {
            const btn = e.target.closest('.edit-period-btn');
            const periodId = btn.dataset.id;
            const period = allPeriods.find(p => p.id == periodId);
            if (period) {
                periodModalTitle.textContent = 'Edit Period';
                document.getElementById('period-id').value = period.id;
                document.getElementById('period-name').value = period.name;
                document.getElementById('period-description').value = period.description || '';
                document.getElementById('period-start-date').value = formatDateForInput(period.start_date);
                document.getElementById('period-end-date').value = formatDateForInput(period.end_date);
                document.getElementById('period-status').value = period.status || 'active';
                openModal(periodModal);
            }
        }
        // Edit Task
        if (e.target.closest('.edit-task-btn')) {
            const btn = e.target.closest('.edit-task-btn');
            const taskId = btn.dataset.id;
            const task = allMajorTasks.find(t => t.id == taskId);
            if (task) {
                taskModalTitle.textContent = 'Edit Major Task';
                document.getElementById('task-id').value = task.id;
                document.getElementById('task-name').value = task.task_name;
                document.getElementById('task-description').value = task.description || '';
                document.getElementById('task-period-id').value = task.period_id || '';
                document.getElementById('task-deadline').value = formatDateForInput(task.deadline);
                document.getElementById('task-priority').value = task.priority || 'Medium';
                document.getElementById('task-status').value = task.status || 'To Do';
                document.getElementById('task-urgency').value = task.urgency || 'Soon';
                document.getElementById('task-importance').value = task.importance || 'Important';
                document.getElementById('task-working-with').value = task.working_with || '';
                taskPercentCompleteSlider.value = task.percent_complete || 0;
                taskPercentValueDisplay.textContent = (task.percent_complete || 0) + '%';
                document.getElementById('task-notes').value = task.notes || '';
                openModal(taskModal);
            }
        }
        // Edit Subtask
        if (e.target.closest('.edit-subtask-btn')) {
            const btn = e.target.closest('.edit-subtask-btn');
            const subtaskId = btn.dataset.id;
            // We need to fetch the subtask details as they are not stored globally in the same way
            try {
                const subtask = await fetchData(`subtasks.php?action=get&id=${subtaskId}`);
                if (subtask) {
                    subtaskModalTitle.textContent = 'Edit Subtask';
                    document.getElementById('subtask-id').value = subtask.id;
                    document.getElementById('subtask-major-task-id').value = subtask.major_task_id;
                    document.getElementById('subtask-name').value = subtask.task_name;
                    document.getElementById('subtask-description').value = subtask.description || '';
                    document.getElementById('subtask-priority').value = subtask.priority || 'Medium';
                    document.getElementById('subtask-deadline').value = formatDateForInput(subtask.deadline);
                    document.getElementById('subtask-status').value = subtask.status || 'To Do';
                    document.getElementById('subtask-urgency').value = subtask.urgency || 'Soon';
                    document.getElementById('subtask-importance').value = subtask.importance || 'Important';
                    document.getElementById('subtask-working-with').value = subtask.working_with || '';
                    subtaskPercentCompleteSlider.value = subtask.percent_complete || 0;
                    subtaskPercentValueDisplay.textContent = (subtask.percent_complete || 0) + '%';
                    document.getElementById('subtask-notes').value = subtask.notes || '';
                    openModal(subtaskModal);
                }
            } catch (error) {
                showGlobalAlert('Failed to load subtask details for editing.', 'error');
            }
        }

        // Delete Item (Period, Task, Subtask)
        if (e.target.closest('.delete-item-btn')) {
            const btn = e.target.closest('.delete-item-btn');
            currentDeleteItem = { type: btn.dataset.type, id: btn.dataset.id, name: btn.dataset.name };
            deleteConfirmMessage.textContent = `Are you sure you want to delete the ${currentDeleteItem.type} "${currentDeleteItem.name}"? This action cannot be undone.`;
            openModal(deleteConfirmModal);
        }
        
        // View Subtasks
        if (e.target.closest('.view-subtasks-btn')) {
            const btn = e.target.closest('.view-subtasks-btn');
            const majorTaskId = btn.dataset.id;
            const majorTaskName = btn.dataset.name;
            loadSubtasksForMajorTask(majorTaskId, majorTaskName);
        }
    });

    confirmDeleteBtn.addEventListener('click', async () => {
        if (!currentDeleteItem) return;
        
        const { type, id } = currentDeleteItem;
        let endpoint = '';
        if (type === 'period') endpoint = 'periods.php';
        else if (type === 'task') endpoint = 'tasks.php';
        else if (type === 'subtask') endpoint = 'subtasks.php';
        else return;

        const deleteBtn = document.getElementById('confirm-delete-btn');
        deleteBtn.dataset.originalText = deleteBtn.textContent;
        showButtonSpinner(deleteBtn, true);

        try {
            const formData = new FormData();
            formData.append('id', id);
            const result = await postData(endpoint, formData, 'delete');
            showGlobalAlert(result.message || `${type.charAt(0).toUpperCase() + type.slice(1)} deleted successfully!`, 'success');
            closeModal(deleteConfirmModal);
            if (type === 'subtask' && subtasksDisplaySection.style.display !== 'none' && addSubtaskBtnGlobal.dataset.majorTaskId) {
                loadSubtasksForMajorTask(addSubtaskBtnGlobal.dataset.majorTaskId, currentMajorTaskNameSpan.textContent);
            }
            loadAllData(); // Refresh all data
        } catch (error) {
            // Error already shown
        } finally {
            showButtonSpinner(deleteBtn, false);
            currentDeleteItem = null;
        }
    });
    
    // Period Filter
    periodFilterSelect.addEventListener('change', async function() {
        const periodId = this.value;
        let endpoint = 'tasks.php?action=list';
        if (periodId !== 'all') {
            endpoint += `&period_id=${periodId}`;
        }
        try {
            const tasks = await fetchData(endpoint);
            renderMajorTasks(tasks);
        } catch (error) {
            // Error handled by fetchData
        }
    });

    // Hide Subtasks
    document.getElementById('hide-subtasks-btn').addEventListener('click', () => {
        subtasksDisplaySection.classList.add('hidden');
        currentMajorTaskNameSpan.textContent = '';
        addSubtaskBtnGlobal.dataset.majorTaskId = '';
    });
    
    // Logout
    document.getElementById('logout-btn').addEventListener('click', () => {
        if (confirm('Are you sure you want to log out?')) {
            window.location.href = 'logout.php';
        }
    });

    // --- Data Loading ---
    async function loadSubtasksForMajorTask(majorTaskId, majorTaskName) {
        currentMajorTaskNameSpan.textContent = htmlspecialchars(majorTaskName);
        addSubtaskBtnGlobal.dataset.majorTaskId = majorTaskId; // Store for adding new subtasks
        subtasksDisplaySection.classList.remove('hidden');
        subtasksDisplaySection.scrollIntoView({ behavior: 'smooth', block: 'start' });
        try {
            const subtasks = await fetchData(`subtasks.php?action=list&major_task_id=${majorTaskId}`);
            renderSubtasks(subtasks);
        } catch (error) {
            // Error handled by fetchData, renderSubtasks will show "no subtasks"
            renderSubtasks([]);
        }
    }

    async function loadAllData() {
        try {
            // Parallel fetching
            const [periods, tasks] = await Promise.all([
                fetchData('periods.php?action=list'),
                fetchData('tasks.php?action=list') // Load all tasks initially
            ]);
            renderPeriods(periods);
            renderMajorTasks(tasks); // This will render all tasks
            // If a period filter was previously selected, re-apply it (optional)
            // Or simply let the user re-select.
        } catch (error) {
            console.error("Failed to load initial dashboard data:", error);
            // Specific error messages are shown by fetchData
        }
    }

    // --- Initial Load ---
    loadAllData();
});
</script>

</body>
</html>
