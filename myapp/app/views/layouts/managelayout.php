<?php
$pagetitle = isset($pagename) ? $pagename : 'Pwo ';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Manage - <?php echo $pagetitle ?></title>

<!-- Google Font: Source Sans Pro -->
<link rel="stylesheet"
	href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
<!-- Font Awesome -->
<link rel="stylesheet"
	href="<?php echo getUrl('assets/manage/plugins/fontawesome-free/css/all.min.css') ?>">
<!-- Ionicons -->
<link rel="stylesheet"
	href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
<!-- Tempusdominus Bootstrap 4 -->
<link rel="stylesheet"
	href="<?php echo getUrl('assets/manage/plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css') ?>">
<!-- iCheck -->
<link rel="stylesheet"
	href="<?php echo getUrl('assets/manage/plugins/icheck-bootstrap/icheck-bootstrap.min.css') ?>">
<!-- JQVMap -->
<link rel="stylesheet"
	href="<?php echo getUrl('assets/manage/plugins/jqvmap/jqvmap.min.css') ?>">
<!-- Theme style -->
<link rel="stylesheet"
	href="<?php echo getUrl('assets/manage/css/adminlte.min.css') ?>">
<!-- overlayScrollbars -->
<link rel="stylesheet"
	href="<?php echo getUrl('assets/manage/plugins/overlayScrollbars/css/OverlayScrollbars.min.css') ?>">
<!-- Daterange picker -->
<link rel="stylesheet"
	href="<?php echo getUrl('assets/manage/plugins/daterangepicker/daterangepicker.css') ?>">
</head>
<body class="hold-transition sidebar-mini layout-fixed">
	<div class="wrapper">

		<!-- Navbar -->
		<nav
			class="main-header navbar navbar-expand navbar-white navbar-light">
			<!-- Left navbar links -->
			<ul class="navbar-nav">
				<li class="nav-item"><a class="nav-link" data-widget="pushmenu"
					href="#" role="button"><i class="fas fa-bars"></i></a></li>
				<li class="nav-item d-none d-sm-inline-block"><a
					href="<?php echo getUrl('manage') ?>" class="nav-link">Home</a></li>
				<li class="nav-item d-none d-sm-inline-block"><a href="#"
					class="nav-link">Contact</a></li>
			</ul>

			<!-- SEARCH FORM -->
			<form class="form-inline ml-3">
				<div class="input-group input-group-sm">
					<input class="form-control form-control-navbar" type="search"
						placeholder="Search" aria-label="Search">
					<div class="input-group-append">
						<button class="btn btn-navbar" type="submit">
							<i class="fas fa-search"></i>
						</button>
					</div>
				</div>
			</form>

			<!-- Right navbar links -->
			<ul class="navbar-nav ml-auto">
				<!-- Messages Dropdown Menu -->
				<li class="nav-item dropdown"><a class="nav-link"
					data-toggle="dropdown" href="#"> <i class="far fa-comments"></i> <span
						class="badge badge-danger navbar-badge">3</span>
				</a>
					<div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
						<a href="#" class="dropdown-item"> <!-- Message Start -->
							<div class="media">
								<img
									src="<?php echo getUrl('assets/manage/img/user1-128x128.jpg') ?>"
									alt="User Avatar" class="img-size-50 mr-3 img-circle">
								<div class="media-body">
									<h3 class="dropdown-item-title">
										Brad Diesel <span class="float-right text-sm text-danger"><i
											class="fas fa-star"></i></span>
									</h3>
									<p class="text-sm">Call me whenever you can...</p>
									<p class="text-sm text-muted">
										<i class="far fa-clock mr-1"></i> 4 Hours Ago
									</p>
								</div>
							</div> <!-- Message End -->
						</a>
						<div class="dropdown-divider"></div>
						<a href="#" class="dropdown-item"> <!-- Message Start -->
							<div class="media">
								<img
									src="<?php echo getUrl('assets/manage/img/user8-128x128.jpg') ?>"
									alt="User Avatar" class="img-size-50 img-circle mr-3">
								<div class="media-body">
									<h3 class="dropdown-item-title">
										John Pierce <span class="float-right text-sm text-muted"><i
											class="fas fa-star"></i></span>
									</h3>
									<p class="text-sm">I got your message bro</p>
									<p class="text-sm text-muted">
										<i class="far fa-clock mr-1"></i> 4 Hours Ago
									</p>
								</div>
							</div> <!-- Message End -->
						</a>
						<div class="dropdown-divider"></div>
						<a href="#" class="dropdown-item"> <!-- Message Start -->
							<div class="media">
								<img
									src="<?php echo getUrl('assets/manage/img/user3-128x128.jpg') ?>"
									alt="User Avatar" class="img-size-50 img-circle mr-3">
								<div class="media-body">
									<h3 class="dropdown-item-title">
										Nora Silvester <span class="float-right text-sm text-warning"><i
											class="fas fa-star"></i></span>
									</h3>
									<p class="text-sm">The subject goes here</p>
									<p class="text-sm text-muted">
										<i class="far fa-clock mr-1"></i> 4 Hours Ago
									</p>
								</div>
							</div> <!-- Message End -->
						</a>
						<div class="dropdown-divider"></div>
						<a href="#" class="dropdown-item dropdown-footer">See All Messages</a>
					</div></li>
				<!-- Notifications Dropdown Menu -->
				<li class="nav-item dropdown"><a class="nav-link"
					data-toggle="dropdown" href="#"> <i class="far fa-bell"></i> <span
						class="badge badge-warning navbar-badge">15</span>
				</a>
					<div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
						<span class="dropdown-item dropdown-header">15 Notifications</span>
						<div class="dropdown-divider"></div>
						<a href="#" class="dropdown-item"> <i class="fas fa-envelope mr-2"></i>
							4 new messages <span class="float-right text-muted text-sm">3
								mins</span>
						</a>
						<div class="dropdown-divider"></div>
						<a href="#" class="dropdown-item"> <i class="fas fa-users mr-2"></i>
							8 friend requests <span class="float-right text-muted text-sm">12
								hours</span>
						</a>
						<div class="dropdown-divider"></div>
						<a href="#" class="dropdown-item"> <i class="fas fa-file mr-2"></i>
							3 new reports <span class="float-right text-muted text-sm">2 days</span>
						</a>
						<div class="dropdown-divider"></div>
						<a href="#" class="dropdown-item dropdown-footer">See All
							Notifications</a>
					</div></li>

				<!--begin::User Menu Dropdown-->
				<?php
    $cuser = getCurrentUser();
    ?>
				
				<li class="nav-item dropdown user-menu"><a href="#"
					class="nav-link dropdown-toggle" data-toggle="dropdown"> <img
						src="<?php echo getUrl('assets/manage/img/user2-160x160.jpg')?>"
						class="user-image rounded-circle shadow" alt="User Image" /> <span
						class="d-none d-md-inline"><?php echo $cuser->realname ?></span>
				</a>
					<ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
						<!--begin::User Image-->
						<li class="user-header text-bg-primary"><img
							src="<?php echo getUrl('assets/manage/img/user2-160x160.jpg')?>"
							class="rounded-circle shadow" alt="User Image" />
							<p>
								<?php echo $cuser->realname ?> - <?php echo $cuser->perms ?>
							</p></li>
						<!--end::User Image-->
						<!--begin::Menu Body-->
						<li class="user-body">
							<!--begin::Row-->
							<div class="row">
								<div class="col-4 text-center">
									<a href="#">Followers</a>
								</div>
								<div class="col-4 text-center">
									<a href="#">Sales</a>
								</div>
								<div class="col-4 text-center">
									<a href="#">Friends</a>
								</div>
							</div> <!--end::Row-->
						</li>
						<!--end::Menu Body-->
						<!--begin::Menu Footer-->
						<li class="user-footer"><a href="#"
							class="btn btn-outline-secondary">Profile</a> <a
							href="<?php echo getUrl('login/logout') ?>"
							class="btn btn-outline-danger float-end">Sign out</a></li>
						<!--end::Menu Footer-->
					</ul></li>
				<!--end::User Menu Dropdown-->

			</ul>
		</nav>
		<!-- /.navbar -->

		<!-- Main Sidebar Container -->
		<aside class="main-sidebar sidebar-dark-primary elevation-4">
			<!-- Brand Logo -->
			<a href="<?php echo getUrl('manage') ?>" class="brand-link"> <span
				class="brand-text font-weight-light">PHPWebObjects</span>
			</a>

			<!-- Sidebar -->
			<div class="sidebar">

				<!-- Sidebar Menu -->
				<nav class="mt-2">
					<ul class="nav nav-pills nav-sidebar flex-column"
						data-widget="treeview" role="menu" data-accordion="false">

						<li class="nav-item"><a href="#" class="nav-link"> <i
								class="nav-icon fas fa-edit"></i>
								<p>
									Forms <i class="fas fa-angle-left right"></i>
								</p>
						</a>
							<ul class="nav nav-treeview">
								<li class="nav-item"><a
									href="<?php echo getUrl('manage/pages/advancedforms') ?>"
									class="nav-link"> <i class="far fa-circle nav-icon"></i>
										<p>Forms</p>
								</a></li>
							</ul></li>
						<li class="nav-item"><a href="#" class="nav-link"> <i
								class="nav-icon fas fa-table"></i>
								<p>
									Tables <i class="fas fa-angle-left right"></i>
								</p>
						</a>
							<ul class="nav nav-treeview">
								<li class="nav-item"><a
									href="<?php echo getUrl('manage/pages/simpletables') ?>"
									class="nav-link"> <i class="far fa-circle nav-icon"></i>
										<p>Simple Tables</p>
								</a></li>
							</ul></li>

					</ul>
				</nav>
				<!-- /.sidebar-menu -->
			</div>
			<!-- /.sidebar -->
		</aside>

		<!-- Content Wrapper. Contains page content -->
		<div class="content-wrapper">
			<!-- section class="content-header">
				<div class="container-fluid">
					<div class="row mb-2">
						<div class="col-sm-6">
							<h1><?php echo isset($pagename) ? $pagename : '' ?></h1>
						</div>
						<div class="col-sm-6">
							<ol class="breadcrumb float-sm-right">
								<li class="breadcrumb-item"><a href="#">Home</a></li>
								<li class="breadcrumb-item active"><?php echo isset($pagename) ? $pagename : '' ?></li>
							</ol>
						</div>
					</div>
				</div>
			</section-->
			<br>

            <?php

            $splashmsgs = res()->getSplashMsg();

            if ($splashmsgs) {
                ?>
              <div class="content">
				<!-- Content Header (Page header) -->
				<div class="container-fluid">
					<div class="row mb-2">
						<div class="col-sm-12">
                					<?php echo $splashmsgs ?>
                			</div>
					</div>
				</div>
				<!-- /.container-fluid -->
			</div>          
            <?php
            }
            ?>
            
            <?php echo isset($mainregion) ? $mainregion : '' ?>  
            
            </div>

		<footer class="main-footer">
			<strong>Copyright &copy; 2014 - <?php echo date('Y') ?> <a href="#">PHP
					Web Objects</a>.
			</strong> All rights reserved.
			<div class="float-right d-none d-sm-inline-block">
				<b>Version</b> 1.0
			</div>
		</footer>

		<!-- Control Sidebar -->
		<aside class="control-sidebar control-sidebar-dark">
			<!-- Control sidebar content goes here -->
		</aside>
		<!-- /.control-sidebar -->
	</div>
	<!-- ./wrapper -->

	<!-- jQuery -->
	<script
		src="<?php echo getUrl('assets/manage/plugins/jquery/jquery.min.js') ?>"></script>
	<!-- jQuery UI 1.11.4 -->
	<script
		src="<?php echo getUrl('assets/manage/plugins/jquery-ui/jquery-ui.min.js') ?>"></script>
	<!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->
	<script>
            $.widget.bridge('uibutton', $.ui.button)
        </script>
	<!-- Bootstrap 4 -->
	<script
		src="<?php echo getUrl('assets/manage/plugins/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
	<!-- ChartJS -->
	<script
		src="<?php echo getUrl('assets/manage/plugins/chart.js/Chart.min.js') ?>"></script>
	<!-- Sparkline -->
	<script
		src="<?php echo getUrl('assets/manage/plugins/sparklines/sparkline.js') ?>"></script>
	<!-- JQVMap -->
	<script
		src="<?php echo getUrl('assets/manage/plugins/jqvmap/jquery.vmap.min.js') ?>"></script>
	<script
		src="<?php echo getUrl('assets/manage/plugins/jqvmap/maps/jquery.vmap.usa.js') ?>"></script>
	<!-- jQuery Knob Chart -->
	<script
		src="<?php echo getUrl('assets/manage/plugins/jquery-knob/jquery.knob.min.js') ?>"></script>
	<!-- daterangepicker -->
	<script
		src="<?php echo getUrl('assets/manage/plugins/moment/moment.min.js') ?>"></script>
	<script
		src="<?php echo getUrl('assets/manage/plugins/daterangepicker/daterangepicker.js') ?>"></script>
	<!-- Tempusdominus Bootstrap 4 -->
	<script
		src="<?php echo getUrl('assets/manage/plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js') ?>"></script>
	<!-- overlayScrollbars -->
	<script
		src="<?php echo getUrl('assets/manage/plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js') ?>"></script>
	<!-- AdminLTE App -->
	<script src="<?php echo getUrl('assets/manage/js/adminlte.min.js') ?>"></script>
	<script src="<?php echo getUrl('assets/manage/js/demo.js') ?>"></script>
	<script
		src="<?php echo getUrl('assets/manage/js/pages/dashboard.js') ?>"></script>
</body>
</html>
