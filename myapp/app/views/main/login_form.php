<div class="login-box">
	<div class="login-logo">
		<b>Login</b>
	</div>
	<!-- /.login-logo -->
	<div class="card">
		<div class="card-body login-card-body">
			<p class="login-box-msg">Sign in to start your session</p>

			<form action="<?php echo getUrl('login') ?>" method="post"
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
				<div class="input-group mb-3">
					<input type="password" class="form-control" placeholder="Password"
						required="required" name="password"
						pattern="(?=^.{6,46}$)((?=.*\d)|(?=.*\W+))(?![.\n])(?=.*[A-Z])(?=.*[a-z]).*$">
					<div class="input-group-append">
						<div class="input-group-text">
							<span class="fas fa-lock"></span>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-8">
						<div class="icheck-primary">
							<input type="checkbox" id="remember"> <label for="remember">
								Remember Me </label>
						</div>
					</div>
					<!-- /.col -->
					<div class="col-4">
						<button type="submit" class="btn btn-primary btn-block">Sign In</button>
					</div>
					<!-- /.col -->
				</div>
			</form>

			<p class="mb-1">
				<a href="<?php echo getUrl('login/forgotpass') ?>">I forgot my
					password</a>
			</p>
			<p class="mb-0"></p>
		</div>
		<!-- /.login-card-body -->
	</div>
</div>
<!-- /.login-box -->