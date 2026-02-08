
<?php $__env->startSection('title', 'Dashboard'); ?>
<?php $__env->startSection('page-title', 'Dashboard Overview'); ?>

<?php $__env->startSection('content'); ?>
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon">
      <i class="fas fa-users"></i>
    </div>
    <div class="stat-value"><?php echo e($stats['users']); ?></div>
    <div class="stat-label">Total Users</div>
  </div>


  <div class="stat-card">
    <div class="stat-icon">
      <i class="fas fa-user-tie"></i>
    </div>
    <div class="stat-value"><?php echo e($stats['trainers']); ?></div>
    <div class="stat-label">Trainers</div>
  </div>

  <div class="stat-card">
    <div class="stat-icon">
      <i class="fas fa-user-graduate"></i>
    </div>
    <div class="stat-value"><?php echo e($stats['trainees']); ?></div>
    <div class="stat-label">Trainees</div>
  </div>

  <div class="stat-card">
    <div class="stat-icon">
      <i class="fas fa-clock"></i>
    </div>
    <div class="stat-value"><?php echo e($stats['pending_requests']); ?></div>
    <div class="stat-label">Pending Requests</div>
  </div>
</div>

<div class="card">
  <h3 style="margin-bottom: 20px; color: var(--dark);">Quick Actions</h3>
  <div style="display: flex; gap: 16px; flex-wrap: wrap;">
    <a href="<?php echo e(route('admin.users.create')); ?>" class="btn btn-primary">
      <i class="fas fa-user-plus"></i>
      Add New User
    </a>
    <a href="<?php echo e(route('admin.templates.create')); ?>" class="btn btn-success">
      <i class="fas fa-file-alt"></i>
      Create Template
    </a>
    <a href="<?php echo e(route('admin.requests.index')); ?>" class="btn btn-warning">
      <i class="fas fa-envelope"></i>
      View Requests
    </a>
    <a href="<?php echo e(route('admin.subscriptions.expired')); ?>" class="btn btn-danger">
      <i class="fas fa-clock"></i>
      Check Expired
    </a>
  </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\laragon\www\Ameen-Gym\resources\views/admin/dashboard.blade.php ENDPATH**/ ?>