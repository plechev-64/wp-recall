<?php global $rcl_user_URL,$rcl_options; ?>

<div id="recallbar">
	<ul class="right-recall-menu">
		<?php rcl_recallbar_rightside(); ?>
	</ul>

	<ul class="left-recall-menu">
	<?php if(is_user_logged_in() && current_user_can('activate_plugins')){ // если залогинен и есть полномочия (тот, кто может активировать плагины - явно большой босс)
			echo '<li><a href="/"><i class="fa fa-home"></i><span>' . __('Home') . '</span></a></li>'
					. '<li><a href="' . admin_url() . '"><i class="fa fa-external-link-square"></i><span>' . __('Dashboard') . '</span></a></li>'
					. '<li><a href="' . $rcl_user_URL . '"><i class="fa fa-user"></i><span>' . __('Personal cabinet','wp-recall') . '</span></a></li>'
					. '<li><a href="' . esc_url(wp_logout_url('/')) . '"><i class="fa fa-sign-out"></i><span>' . __('Log Out') . '</span></a></li>';
	}

	else if(is_user_logged_in()){ // если это обычный залогиненный пользователь
			echo '<li><a href="/"><i class="fa fa-home"></i><span>' . __('Home') . '</span></a></li>'
					. '<li><a href="' . $rcl_user_URL . '"><i class="fa fa-user"></i><span>' . __('Personal cabinet','wp-recall') . '</span></a></li>'
					. '<li><a href="' . esc_url(wp_logout_url('/')) . '"><i class="fa fa-sign-out"></i><span>' . __('Log Out') . '</span></a></li>';
	}

	else {	// если гость
			// и в настройках выбрано:
		if($rcl_options['login_form_recall']==1){ // Каждая загружается отдельно
			$page_in_out = rcl_format_url(get_permalink($rcl_options['page_login_form_recall'])); // страница с формой входа-регистрации
			echo '<li><a href="/"><i class="fa fa-home"></i><span>' . __('Home') . '</span></a></li>'
					. '<li><a href="' . $page_in_out . 'action-rcl=register"><i class="fa fa-book"></i><span>' . __('Register') . '</span></a></li>'
					. '<li><a href="' . $page_in_out . 'action-rcl=login"><i class="fa fa-sign-in"></i><span>' . __('Entry','wp-recall') . '</span></a></li>';
		}
		else if($rcl_options['login_form_recall']==2){ // Формы Wordpress
			echo '<li><a href="/"><i class="fa fa-home"></i><span>' . __('Home') . '</span></a></li>';
			if (get_option('users_can_register') ) { // если в настройках вордпресса разрешена регистрация - выводим
				echo '<li><a href="' . esc_url(wp_registration_url()) . '"><i class="fa fa-book"></i><span>' . __('Register') . '</span></a></li>';
			}
			echo '<li><a href="' . esc_url(wp_login_url('/')) . '"><i class="fa fa-sign-in"></i><span>' . __('Entry','wp-recall') . '</span></a></li>';
		}
		else if($rcl_options['login_form_recall']==3){ // Форма в виджете
			echo '<li><a href="/"><i class="fa fa-home"></i><span>' . __('Home') . '</span></a></li>';
		}
		else if(!$rcl_options['login_form_recall']){ //  Всплывающая форма
			echo '<li><a href="/"><i class="fa fa-home"></i><span>' . __('Home') . '</span></a></li>'
					. '<li><a href="#" class="rcl-register"><i class="fa fa-book"></i><span>' . __('Register') . '</span></a></li>'
					. '<li><a href="#" class="rcl-login"><i class="fa fa-sign-in"></i><span>' . __('Entry','wp-recall') . '</span></a></li>';
		}
	} ?>
	</ul>

<?php wp_nav_menu('fallback_cb=null&container_class=recallbar&link_before=<i class=\'fa fa-caret-right\'></i>&theme_location=recallbar'); ?>

<?php if ( is_admin_bar_showing() ){ ?>
		<style>#recallbar{margin-top:28px;}</style>
<?php } ?>

</div>
<div id="favs" style="display:none"></div>
<div id="add_bookmarks" style="display:none"></div>