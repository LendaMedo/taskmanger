<?php
/**
 * MyWorkHub - Main Landing Page (index.php)
 *
 * This page serves as the entry point to the application.
 * - If the user is logged in, it redirects to the dashboard.
 * - If not logged in, it displays a public landing page with login/register options.
 *
 * @author Dr. Ahmed AL-sadi
 * @version 1.0
 */

// Define ROOT_PATH for consistent includes. Assumes index.php is in the 'workhub' directory.
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__);
}

// Include necessary files
// It's crucial that the paths within these included files are correct
// relative to their own location or use ROOT_PATH if modified to do so.
require_once ROOT_PATH . '/includes/config.php'; // Defines constants like SITE_NAME
require_once ROOT_PATH . '/auth/session.php';   // Handles session initialization and functions like isLoggedIn()
                                                // session.php should also include functions.php which includes db.php

// Check if the user is already logged in
if (isLoggedIn()) {
    // If logged in, redirect to the dashboard
    // The redirect function is expected to be in functions.php, included via session.php
    redirect('dashboard.php');
    exit; // Ensure no further code is executed after redirection
}

// If not logged in, the rest of the page (HTML) will be displayed.
$pageTitle = "Welcome to " . SITE_NAME;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f4f7f6; /* A light, neutral background */
        }
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); /* Indigo to purple gradient */
        }
        .feature-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .cta-button {
            transition: background-color 0.3s ease, transform 0.3s ease;
        }
        .cta-button:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body class="flex flex-col min-h-screen">

    <nav class="bg-white shadow-md sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center">
                    <a href="index.php" class="flex-shrink-0">
                        <i class="fas fa-tasks text-3xl text-indigo-600"></i>
                        <span class="ml-2 text-2xl font-bold text-indigo-600"><?php echo htmlspecialchars(SITE_NAME); ?></span>
                    </a>
                </div>
                <div class="hidden md:block">
                    <div class="ml-10 flex items-baseline space-x-4">
                        <a href="login.php" class="text-gray-700 hover:bg-indigo-500 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Login</a>
                        <a href="register.php" class="bg-indigo-600 text-white hover:bg-indigo-700 px-3 py-2 rounded-md text-sm font-medium">Register</a>
                    </div>
                </div>
                <div class="-mr-2 flex md:hidden">
                    <button type="button" id="mobile-menu-button" class="bg-white inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500" aria-controls="mobile-menu" aria-expanded="false">
                        <span class="sr-only">Open main menu</span>
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
            </div>
        </div>
        <div class="md:hidden hidden" id="mobile-menu">
            <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
                <a href="login.php" class="text-gray-700 hover:bg-indigo-500 hover:text-white block px-3 py-2 rounded-md text-base font-medium">Login</a>
                <a href="register.php" class="bg-indigo-600 text-white hover:bg-indigo-700 block px-3 py-2 rounded-md text-base font-medium">Register</a>
            </div>
        </div>
    </nav>

    <section class="hero-section text-white py-20 md:py-32">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-4xl md:text-6xl font-bold leading-tight mb-6">
                Organize Your Work, Achieve Your Goals
            </h1>
            <p class="text-lg md:text-xl text-indigo-100 mb-10 max-w-3xl mx-auto">
                <?php echo htmlspecialchars(SITE_NAME); ?> helps you manage your projects, tasks, and deadlines with ease. Boost your productivity and stay on top of your work like never before.
            </p>
            <div class="space-y-4 sm:space-y-0 sm:space-x-4">
                <a href="register.php" class="cta-button bg-white text-indigo-700 hover:bg-gray-100 text-lg font-semibold py-3 px-8 rounded-lg shadow-md inline-block">
                    Get Started Free
                </a>
                <a href="login.php" class="cta-button bg-transparent border-2 border-white text-white hover:bg-white hover:text-indigo-700 text-lg font-semibold py-3 px-8 rounded-lg shadow-md inline-block">
                    Login to Your Account
                </a>
            </div>
        </div>
    </section>

    <section class="py-16 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-800">Why Choose <?php echo htmlspecialchars(SITE_NAME); ?>?</h2>
                <p class="text-gray-600 mt-2">Streamline your workflow with our powerful features.</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="feature-card bg-white p-8 rounded-lg shadow-lg text-center">
                    <div class="text-indigo-600 mb-4">
                        <i class="fas fa-tasks fa-3x"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">Intuitive Task Management</h3>
                    <p class="text-gray-600">
                        Easily create, assign, and track major tasks and subtasks. Set priorities, deadlines, and monitor progress.
                    </p>
                </div>
                <div class="feature-card bg-white p-8 rounded-lg shadow-lg text-center">
                    <div class="text-indigo-600 mb-4">
                        <i class="fas fa-calendar-alt fa-3x"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">Organized Work Periods</h3>
                    <p class="text-gray-600">
                        Structure your work into manageable periods (e.g., quarters, sprints) for better planning and overview.
                    </p>
                </div>
                <div class="feature-card bg-white p-8 rounded-lg shadow-lg text-center">
                    <div class="text-indigo-600 mb-4">
                        <i class="fas fa-chart-line fa-3x"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">Progress Tracking</h3>
                    <p class="text-gray-600">
                        Visualize your progress with clear dashboards and completion percentages to stay motivated and informed.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <section class="py-16 bg-indigo-700 text-white">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl font-bold mb-6">Ready to Take Control of Your Workflow?</h2>
            <p class="text-lg text-indigo-100 mb-8">
                Sign up today and experience a new level of organization and productivity with <?php echo htmlspecialchars(SITE_NAME); ?>.
            </p>
            <a href="register.php" class="cta-button bg-white text-indigo-700 hover:bg-gray-100 text-xl font-semibold py-4 px-10 rounded-lg shadow-md">
                Create Your Account
            </a>
        </div>
    </section>

    <footer class="bg-gray-800 text-gray-300 py-12 mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <div class="mb-4">
                <a href="#" class="text-gray-400 hover:text-white px-3"><i class="fab fa-facebook-f"></i></a>
                <a href="#" class="text-gray-400 hover:text-white px-3"><i class="fab fa-twitter"></i></a>
                <a href="#" class="text-gray-400 hover:text-white px-3"><i class="fab fa-linkedin-in"></i></a>
                <a href="#" class="text-gray-400 hover:text-white px-3"><i class="fab fa-github"></i></a>
            </div>
            <p class="text-sm">
                &copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars(SITE_NAME); ?>. Created by Dr. Ahmed AL-sadi. All Rights Reserved.
            </p>
            <p class="text-xs mt-2">
                <a href="#" class="hover:text-white">Privacy Policy</a> | <a href="#" class="hover:text-white">Terms of Service</a>
            </p>
        </div>
    </footer>

    <script>
        // Mobile menu toggle
        const mobileMenuButton = document.getElementById('mobile-menu-button');
        const mobileMenu = document.getElementById('mobile-menu');

        mobileMenuButton.addEventListener('click', () => {
            const expanded = mobileMenuButton.getAttribute('aria-expanded') === 'true' || false;
            mobileMenuButton.setAttribute('aria-expanded', !expanded);
            mobileMenu.classList.toggle('hidden');
            const icon = mobileMenuButton.querySelector('i');
            icon.classList.toggle('fa-bars');
            icon.classList.toggle('fa-times');
        });
    </script>

</body>
</html>
