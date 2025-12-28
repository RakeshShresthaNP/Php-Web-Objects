<?php $splashmsgs[] = res()->getSplashMsg(); ?>
<?php echo ((isset($splashmsgs) && is_array($splashmsgs)) ? implode("<br />\n", $splashmsgs) : ''); ?>

<div class="login-box">
	<div class="login-logo">
		<b>Forgot Password</b>
	</div>
	<!-- /.login-logo -->
	<div class="card">
		<div class="card-body login-card-body">
			<p class="login-box-msg">You forgot your password? Here you can
				easily retrieve a new password.</p>

			<form action="<?php echo getUrl('login/forgotpass') ?>" method="post"
				enctype="multipart/form-data">
				<div class="input-group mb-3">
					<input type="email" class="form-control" placeholder="Email"
						required="required" name="username">
					<div class="input-group-append">
						<div class="input-group-text">
							<span class="fas fa-envelope"></span>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-12">
						<button type="submit" class="btn btn-primary btn-block">Request
							new password</button>
					</div>
					<!-- /.col -->
				</div>
			</form>

			<p class="mt-3 mb-1">
				<a href="<?php echo getUrl('login') ?>">Login</a>
			</p>
			<p class="mb-0"></p>
		</div>
		<!-- /.login-card-body -->
	</div>
</div>
