
<!-- Main content -->
<section class="content">
	<div class="container-fluid">
		<div class="row">
			<div class="col-12">
				<div class="card">
					<div class="card-header">
						<h3 class="card-title">
							<a href="<?php echo getUrl('manage/users') ?>">Users</a>
						</h3>
					</div>
					<!-- /.card-header -->
					<div class="card-body">
                    	<?php if (!empty($_SESSION['flash_errors'])) { ?>
                        <div class="alert alert-danger">
                    	<ul>
                        <?php
                        foreach ($_SESSION['flash_errors'] as $field => $messages) {
                            foreach ($messages as $msg) {
                                ?>
                                <li><strong><?php echo ucfirst($field); ?>:</strong> <?php echo $msg; ?></li>
                        <?php                    
                            }
                        }
                        ?>
                        </ul>
                    	</div>
                        <?php 
                        unset($_SESSION['flash_errors']);
                        }
                        ?>
						<form
							action="<?php echo getUrl('manage/users/edit') ?>/<?php echo $user->id ?>"
							method="post" id="editprofile" name="editprofile"
							enctype="multipart/form-data">
							<input type="hidden" id="id" name="id"
								value="<?php echo $user->id; ?>">
							<table width="1002" border="0">
								<tr>
									<td>Name</td>
									<td><input type="text" id="realname" required="required"
										name="realname" value="<?php echo $user->realname; ?>"></td>
								</tr>
								<tr>
									<td>Home Path</td>
									<td><input type="text" id="homepath" required="required"
										value="<?php echo $user->homepath; ?>" name="homepath"></td>
								</tr>
								<tr>
									<td>Email</td>
									<td><input type="email" id="email" autocomplete="off"
										value="<?php echo $user->email; ?>" required="required"
										name="email"> <span id="handle_status"> </span></td>
								</tr>
								<tr>
									<td>Password</td>
									<td><input type="password" id="password" value=""
										name="password" autocomplete="off"
										pattern="(?=^.{6,46}$)((?=.*\d)|(?=.*\W+))(?![.\n])(?=.*[A-Z])(?=.*[a-z]).*$">
										<br> <span id="pass_chek1"></span>
										<div
											style="color: grey; font-size: 12px; line-height: 16px; margin-left: 10px; margin-top: 3px; width: 329px;">Password
											must be 6 characters including one uppercase letter and
											number.</div></td>
								</tr>
								<tr>
									<td>Confirm Password</td>
									<td><input type="password" id="confirm_password" value=""
										name="confirm_password"> <span id="pass_chek"></span></td>
								</tr>
								<tr>
									<td>&nbsp;</td>
									<td><input type="submit" value="Update Details" id="submit"
										class="" name="submit" style="margin-left: 10px;"></td>
								</tr>
							</table>
					
					</div>
					<!-- /.card-body -->
				</div>
				<!-- /.card -->

			</div>
			<!-- /.col -->
		</div>
		<!-- /.row -->
	</div>
	<!-- /.container-fluid -->
</section>
<!-- /.content -->

<script type="text/javascript">
    $(document).ready(function () {
        $("#editprofile").submit(function (event) {
            var pass1 = document.getElementById("password").value;
            var pass2 = document.getElementById("confirm_password").value;

            if (pass1 != pass2) {
                $('#pass_chek').html('Password Not Match');
                $('#iserror2').val(1);
            } else {
                $('#pass_chek').html('');
                $('#iserror2').val(0);
            }

            if (pass1 && pass1.length < 6) {
                $('#pass_chek').html('Password must be minimum 6 characters');
                passerror = 1;
            } else {
                $('#pass_chek').html('');
                passerror = 0;
            }

            if ($('#iserror1').val() == 1 || $('#iserror2').val() == 1) {
                event.preventDefault();
            } else {
                return;
            }

        });

    });
</script>