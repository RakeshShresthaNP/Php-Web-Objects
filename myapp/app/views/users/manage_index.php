
<!-- Main content -->
<section class="content">
	<div class="container-fluid">
		<div class="row">
			<div class="col-12">
				<div class="card">
					<div class="card-header">
						<h3 class="card-title">
							<a href="<?php echo getUrl('manage/users/add') ?>">Add User</a>
						</h3>
					</div>
					<!-- /.card-header -->
					<div class="card-body">
						<table id="example2" class="table table-bordered table-hover">
							<thead>
								<tr>
									<th><strong>Username</strong></th>
									<th><strong>Name</strong></th>
									<th><strong>Country</strong></th>
									<th><strong>Registered Date</strong></th>
									<th><strong>Status</strong></th>
									<th><strong>Action</strong></th>
								</tr>
							</thead>
							<tbody>
                                    <?php
                                    $i = 1;
                                    foreach ($users as $user) {
                                        ?>
                                        <tr>
									<td><?php echo $user->username; ?> </td>
									<td><?php echo $user->firstname; ?> <?php echo $user->lastname; ?></td>
									<td><?php echo $user->country ? getCountryList($user->country) : ''; ?></td>
									<td><?php echo date('F d, Y', strtotime($user->created)); ?></td>
									<td><?php echo $user->status == 1 ? 'Enabled' : 'Disabled'; ?></td>
									<td><a
										href="<?php echo getUrl('manage/users/edit') ?>/<?php echo $user->id ?>">
											Edit </a>
                                                | 
                                                <?php
                                        if ($user->status == 1) {
                                            ?>
                                                    <a
										href="<?php echo getUrl('manage/users/disable') ?>/<?php echo $user->id ?>">
											Disable </a>
                                                    <?php
                                        }
                                        ?>
                                                <?php
                                        if ($user->status == 2) {
                                            ?>
                                                    <a
										href="<?php echo getUrl('manage/users/enable') ?>/<?php echo $user->id ?>">
											Enable </a>
                                                    <?php
                                        }
                                        ?>
                                            </td>
								</tr>
                                        <?php
                                        $i ++;
                                    }
                                    ?>
								</tbody>
							<tfoot>
								<tr>
									<th><strong>Username</strong></th>
									<th><strong>Name</strong></th>
									<th><strong>Country</strong></th>
									<th><strong>Registered Date</strong></th>
									<th><strong>Status</strong></th>
									<th><strong>Action</strong></th>
								</tr>
							</tfoot>
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
