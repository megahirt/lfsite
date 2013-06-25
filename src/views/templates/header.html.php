		<div id="header" class="png_bg">
			
			<div class="container">
				
				<div class="sf-logo-large">
					<img src="/images/sf_logo_large.png" alt="Scripture Forge" width="96" height="165" class="png_bg" />
				</div>
				
				<div id="header-nav" class="left">
					<ul class="sf-menu">
						<li><a href="/">Home</a></li>
						<li><a href="#">Explore</a>
							<ul>
								<li><a href="#">Jamaica Project 1</a></li>
								<!--
								<li><a href="#">Sub Menu Item 2</a>
									<ul>
										<li><a href="#">Another Sub Menu Item 1</a></li>
										<li><a href="#">Another Sub Menu Item 2</a></li>
										<li><a href="#">Another Sub Menu Item 3</a></li>
									</ul>
								</li>
								-->
								<li><a href="#">Jamaica Project 2</a></li>
								<li><a href="#">Jamaica Project 3</a></li>
							</ul>
						</li>
						<li><a href="/learn_scripture_forge">Learn</a>
							<ul>
								<li><a href="/learn_scripture_forge">About Scripture Forge</a></li>
								<li><a href="/learn_expand_your_team">Expand Your Team</a></li>
								<li><a href="/learn_contribute">Contribute</a></li>
							</ul>
						</li>
						<li><a href="/contribute">Contribute</a></li>
						<li><a href="/discuss">Discuss</a></li>
					</ul>
						<?php if ($is_admin):?>
							<ul style="margin-left: 100px" class="sf-menu">
								<li><a href="#">Administration</a>
									<ul>
										<li><a href="/app/admin">Users and Projects</a></li>
									</ul>
								</li>
							</ul>
						<?php endif;?>
				</div>
				
				<?php if ($logged_in):?>
					<div id="account" class="right">
						<img src="<?php echo $small_gravatar_url; ?>" style="float:left; position:relative; top:-4px; border:1px solid white; margin-right:10px" />
						<span><a href="/account">Welcome, <?php echo $user_name; ?></a></span>
						<span> | <a href="/auth/logout">Logout</a></span>
					</div>
				
				<?php else:?>
					<div id="account" class="right">
						<input type="button" value="Login" class="login-btn left" onclick="window.location='auth/login'"/> &nbsp; or &nbsp; <a href="#">Create an Account</a>
					</div>
				<?php endif;?>
				
			</div>
		</div>