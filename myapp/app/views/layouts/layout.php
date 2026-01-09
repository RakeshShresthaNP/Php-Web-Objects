<?php
$pagetitle = isset($pagename) ? $pagename : SITE_TITLE;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo $pagetitle ?></title>

<!-- Google Font: Source Sans Pro -->
<link rel="stylesheet"
	href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
<!-- Font Awesome -->
<link rel="stylesheet"
	href="<?php echo getUrl('assets/dashboard/plugins/fontawesome-free/css/all.min.css') ?>">
<!-- icheck bootstrap -->
<link rel="stylesheet"
	href="<?php echo getUrl('assets/dashboard/plugins/icheck-bootstrap/icheck-bootstrap.min.css') ?>">
<!-- Theme style -->
<link rel="stylesheet"
	href="<?php echo getUrl('assets/dashboard/dist/css/adminlte.min.css') ?>">
</head>
<body class="hold-transition login-page">

        <?php $splashmsgs = res()->getSplashMsg(); ?>
        
        <?php echo $splashmsgs ?>
        
        <?php echo isset($mainregion) ? $mainregion : '' ?>  

    <!-- jQuery -->
	<script
		src="<?php echo getUrl('assets/dashboard/plugins/jquery/jquery.min.js') ?>"></script>
	<!-- Bootstrap 4 -->
	<script
		src="<?php echo getUrl('assets/dashboard/plugins/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
	<!-- AdminLTE App -->
	<script src="<?php echo getUrl('assets/dashboard/dist/js/adminlte.min.js') ?>"></script>

</body>
</html>