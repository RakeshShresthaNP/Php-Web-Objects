
<!-- ===== Page Wrapper Start ===== -->
<div class="relative p-6 bg-white z-1 dark:bg-gray-900 sm:p-0">
	<div
		class="relative flex flex-col justify-center w-full h-screen dark:bg-gray-900 sm:p-0 lg:flex-row">
		<!-- Form -->
		<div class="flex flex-col flex-1 w-full lg:w-1/2">
			<div class="w-full max-w-md pt-10 mx-auto"></div>
			<div
				class="flex flex-col justify-center flex-1 w-full max-w-md mx-auto">
				<div>
					<div class="mb-5 sm:mb-8">
						<h1
							class="mb-2 font-semibold text-gray-800 text-title-sm dark:text-white/90 sm:text-title-md">
							Forgot Password</h1>
						<p class="text-sm text-gray-500 dark:text-gray-400">You forgot
							your password? Here you can easily retrieve a new password.</p>

                            <?php $splashmsgs = res()->getSplashMsg(); ?>
                            
                            <?php echo $splashmsgs ?>
					</div>
					<div>
						<form action="<?php echo getUrl('login/forgotpass') ?>"
							method="post" enctype="multipart/form-data">
							<div class="space-y-5">
								<!-- Email -->
								<div>
									<label
										class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
										Email<span class="text-error-500">*</span>
									</label> <input type="email" id="username" name="username"
										placeholder="info@gmail.com"
										class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800" />
								</div>
								<!-- Checkbox -->
								<div class="flex items-center justify-between">
									<a href="<?php echo getUrl('login') ?>"
										class="text-sm text-brand-500 hover:text-brand-600 dark:text-brand-400">Login
									</a>
								</div>
								<!-- Button -->
								<div>
									<button
										class="flex items-center justify-center w-full px-4 py-3 text-sm font-medium text-white transition rounded-lg bg-brand-500 shadow-theme-xs hover:bg-brand-600">
										Request new password</button>
								</div>
							</div>
						</form>
						<div class="mt-5"></div>
					</div>
				</div>
			</div>
		</div>

		<div
			class="relative items-center hidden w-full h-full bg-brand-950 dark:bg-white/5 lg:grid lg:w-1/2">
			<div class="flex items-center justify-center z-1">
				<!-- ===== Common Grid Shape Start ===== -->
				<div
					class="absolute right-0 top-0 -z-1 w-full max-w-[250px] xl:max-w-[450px]">
					<img
						src="<?php echo getUrl('assets/dashboard/images/shape/grid-01.svg') ?>"
						alt="grid" />
				</div>
				<div
					class="absolute bottom-0 left-0 -z-1 w-full max-w-[250px] rotate-180 xl:max-w-[450px]">
					<img
						src="<?php echo getUrl('assets/dashboard/images/shape/grid-01.svg') ?>"
						alt="grid" />
				</div>

				<div class="flex flex-col items-center max-w-xs">
					<p class="text-center text-gray-400 dark:text-white/60">Page Info</p>
				</div>
			</div>
		</div>

	</div>
</div>
<!-- ===== Page Wrapper End ===== -->
