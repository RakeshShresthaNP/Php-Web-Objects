<?php
$pagetitle = isset($pagename) ? $pagename : 'Pwo ';
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport"
	content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0" />
<meta http-equiv="X-UA-Compatible" content="ie=edge" />
<title><?php echo $pagetitle ?></title>
<link rel="icon" href="<?php echo getUrl('favicon.ico') ?>">
<link href="<?php echo getUrl('assets/dashboard/style.css') ?>"
	rel="stylesheet">
</head>
<body
	x-data="{ page: 'comingSoon', 'loaded': true, 'darkMode': true, 'stickyMenu': false, 'sidebarToggle': false, 'scrollTop': false }"
	:class="{'dark bg-gray-900': darkMode === true}">
	<!-- ===== Preloader Start ===== -->
	<div x-show="loaded"
		x-init="window.addEventListener('DOMContentLoaded', () => {setTimeout(() => loaded = false, 500)})"
		class="fixed left-0 top-0 z-999999 flex h-screen w-screen items-center justify-center bg-white dark:bg-black">
		<div
			class="h-16 w-16 animate-spin rounded-full border-4 border-solid border-brand-500 border-t-transparent"></div>
	</div>

	<!-- ===== Preloader End ===== -->
    
    <?php echo isset($mainregion) ? $mainregion : '' ?>

	<script defer src="<?php echo getUrl('assets/dashboard/bundle.js') ?>"></script>
</body>
</html>
