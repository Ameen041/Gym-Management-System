<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo $__env->yieldContent('title','Admin'); ?> | Ameen Gym</title>
  <link rel="stylesheet" href="<?php echo e(asset('css/admin.css')); ?>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="admin">
  <div class="admin-wrapper">
    <!-- Sidebar -->
    <aside class="sidebar">
      <div class="sidebar-header">
        <div class="logo">
          <i class="fas fa-dumbbell"></i>
          <div>
            <h1>Ameen Gym</h1>
            <p>Admin Panel</p>
          </div>
        </div>
      </div>

      <nav class="sidebar-nav">
        <a href="<?php echo e(route('admin.dashboard')); ?>" class="<?php echo e(request()->routeIs('admin.dashboard')?'active':''); ?>">
          <i class="fas fa-chart-line"></i>
          <span>Dashboard</span>
        </a>
        
        <a href="<?php echo e(route('admin.users.index')); ?>" class="<?php echo e(request()->routeIs('admin.users.*')?'active':''); ?>">
          <i class="fas fa-users"></i>
          <span>Users</span>
        </a>
        
        <a href="<?php echo e(route('admin.requests.index')); ?>" class="<?php echo e(request()->routeIs('admin.requests.*')?'active':''); ?>">
          <i class="fas fa-envelope"></i>
          <span>Requests</span>
        </a>
        
        <a href="<?php echo e(route('admin.templates.index')); ?>" class="<?php echo e(request()->routeIs('admin.templates.*')?'active':''); ?>">
          <i class="fas fa-file-alt"></i>
          <span>Templates</span>
        </a>
        
        <a href="<?php echo e(route('admin.payments.dashboard')); ?>" class="<?php echo e(request()->routeIs('admin.payments.*')?'active':''); ?>">
          <i class="fas fa-credit-card"></i>
          <span>Payments</span>
        </a>
        
        <a href="<?php echo e(route('admin.subscriptions.expired')); ?>" class="<?php echo e(request()->routeIs('admin.subscriptions.*')?'active':''); ?>">
          <i class="fas fa-clock"></i>
          <span>Expired</span>
        </a>
      </nav>

      <div class="sidebar-footer">
        <form method="POST" action="<?php echo e(url('/logout')); ?>">
          <?php echo csrf_field(); ?>
          <button type="submit" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
          </button>
        </form>
      </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
      <!-- Top Header -->
      <header class="top-header">
        <div class="header-left">
          <button class="sidebar-toggle">
            <i class="fas fa-bars"></i>
          </button>
          <h2><?php echo $__env->yieldContent('page-title', 'Dashboard'); ?></h2>
        </div>
        <div class="header-right">
          <div class="admin-info">
            <i class="fas fa-user-circle"></i>
            <span><?php echo e(auth()->user()->name ?? 'Admin'); ?></span>
          </div>
        </div>
      </header>

      <!-- Page Content -->
      <div class="content-area">
        <?php if(session('status')): ?>
          <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo e(session('status')); ?>

          </div>
        <?php endif; ?>
        
        <?php if(session('success')): ?>
          <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo e(session('success')); ?>

          </div>
        <?php endif; ?>
        
        <?php if(session('error')): ?>
          <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo e(session('error')); ?>

          </div>
        <?php endif; ?>

        <?php echo $__env->yieldContent('content'); ?>
      </div>

      <!-- Footer -->
      <footer class="main-footer">
        <p>© <?php echo e(date('Y')); ?> Ameen Gym — Admin Panel v1.0</p>
      </footer>
    </main>
  </div>

  <script src="<?php echo e(asset('js/admin.js')); ?>"></script>
</body>
</html><?php /**PATH C:\laragon\www\Ameen-Gym\resources\views/layouts/admin.blade.php ENDPATH**/ ?>