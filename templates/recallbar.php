<?php global $rcl_user_URL,$rcl_options; ?>


<?php if (1==2){

// START Можно вывести настройку у реколлбара - "Recall цвет панели в стиле кнопок"
    global $rcl_options;

	$lcp_hex = $rcl_options['primary-color'];               // достаем оттуда наш цвет
	list($r, $g, $b) = sscanf($lcp_hex, "#%02x%02x%02x");   // разбиваем строку на нужный нам формат
	$rs = round($r * 0.45);
	$gs = round($g * 0.45);
	$bs = round($b * 0.45);
	$rt = round($r + (0.25 * (255 - $r)));
	$gt = round($g + (0.25 * (255 - $g)));
	$bt = round($b + (0.25 * (255 - $b)));
	
	// $r $g $b - родные цвета от кнопки
	// $rs $gs $bs - оттенок от кнопки
	// $rt $gt $bt - светлее от оттенка
	echo '<style>
body #recallbar_new {
	background:rgba('.$rs.','.$gs.','.$bs.',0.8);
}
#recallbar_new .rcb_menu,
#recallbar_new .pr_sub_menu {
	border-top: 2px solid rgba('.$rt.','.$gt.','.$bt.',0.8);
}
#recallbar_new .pr_menu:hover {
	border-left: 2px solid rgba('.$rt.','.$gt.','.$bt.',0.8);
}
#recallbar_new .pr_menu .fa-ellipsis-h {
	color: rgba('.$rt.','.$gt.','.$bt.',0.8);
}
#recallbar_new .rcb_nmbr {
	background: rgba('.$rt.','.$gt.','.$bt.',0.8);
}
</style>';
// END


/*
Меню вызывал так:
	register_nav_menus( array(
		'recallbar_left' => 'Меню в реколлбаре слева',
	) );
*/

 ?>
<div id="recallbar_new" class="my_recallbar">
	<div class="rcb_left">
		<div class="pr_left_menu">
			<i class="fa fa-bars" aria-hidden="true"></i>
			<?php wp_nav_menu(array( 'theme_location' => 'recallbar','container_class'=>'rcb_menu','fallback_cb' => '__return_empty_string')); ?>
		</div>
		<div class="rcb_icon">
			<a href="/">
				<i class="fa fa-home" aria-hidden="true"></i>
				<div class="rcb_hiden"><span>Home</span></div>
			</a>
		</div>
		

		
		<div class="rcb_icon">
			<a href="/" class="rcl-register">
				<i class="fa fa-book" aria-hidden="true"></i><span>Регистрация</span>
				<div class="rcb_hiden"><span>Регистрация</span></span></div>
			</a>
		</div>
	</div>

	<div class="rcb_right">
		<div class="rcb_icon">
			<a href="/">
				<i class="fa fa-shopping-cart" aria-hidden="true"></i>
				<div class="rcb_hiden"><span>Корзина</span></div>
			</a>
			<div class="rcb_nmbr">1</div>
		</div>
		<div class="rcb_icon">
			<a href="/">
				<i class="fa fa-bullhorn" aria-hidden="true"></i>
				<div class="rcb_hiden"><span>Оповещения</span></div>
			</a>
			<div class="rcb_nmbr">26</div>
		</div>
		<div class="rcb_icon">
			<a href="/">
				<i class="fa fa-plus" aria-hidden="true"></i>
				<div class="rcb_hiden"><span>В закладки</span></div>
			</a>
		</div>
		<div class="rcb_icon">
			<a href="/">
				<i class="fa fa-bookmark" aria-hidden="true"></i>
				<div class="rcb_hiden"><span>Закладки</span></div>
			</a>
		</div>
		<div class="pr_menu">
			<i class="fa fa-ellipsis-h" aria-hidden="true"></i>
			<img alt="" src="http://across-ocean.otshelnik-fm.ru/wp-content/uploads/rcl-uploads/avatars/1-70.jpg">
			<div class="pr_sub_menu">
				<div class="rcb_line"><a href="/"><i class="fa fa-user" aria-hidden="true"></i><span>В личный кабинет</span></a></div>
				<div class="rcb_line"><a href="/"><i class="fa fa-user-secret" aria-hidden="true"></i><span>Настройки профиля</span></a></div>
				<div class="rcb_line"><a href="/"><i class="fa fa-pencil" aria-hidden="true"></i><span>Добавить запись</span></a></div>
				<div class="rcb_line"><a href="/"><i class="fa fa-external-link-square" aria-hidden="true"></i><span>В админку</span></a></div>
				<div class="rcb_line"><a href="/"><i class="fa fa-sign-out" aria-hidden="true"></i><span>Выход</span></a></div>
			</div>
		</div>
	</div>
	
</div>

<script>
(function($){
	jQuery(document).ready(function($){
		// раскрываем нужное субменю
		$("#recallbar_new .menu-item-has-children").hover(function() {
			$(this).children(".sub-menu").css({'visibility': 'visible'})
		}, function() {
			$(this).children(".sub-menu").css({'visibility': ''})
		});
	});
})(jQuery);
</script>


<style>
/* Реколлбар общие */
.my_recallbar {
    background: rgba(0, 0, 0, 0.8);
    font-size: 14px;
    height: 36px;
    left: 0;
	line-height: 1;
    position: fixed;
    top: 0;
    width: 100%;
    z-index: 9999;
}
.my_recallbar .fa {
    color: #e6e6e6;
    font-size: 30px;
    line-height: 36px;
	width: 30px;
}
.rcb_icon:hover .fa {
    color: #d4d4d4;
}
.rcb_icon {
    display: inline-block;
	margin: 0 10px;
	position: relative;
}
.rcb_icon a {
    display: inline-block;
}
.rcl-register span {
    color: #e6e6e6;
    margin: 0 0 0 7px;
    vertical-align: super;
}
.rcb_icon .rcb_hiden {
    margin: 0 0 0 -43px;
    opacity: 0;
    pointer-events: none;
    position: absolute;
    text-align: center;
    top: 54px;
    transform: translate3d(0px, 0px, 0px);
    transition: opacity 0.2s ease-in-out 0s,
				visibility 0.2s ease-in-out 0s,
				transform 0.2s cubic-bezier(0.71, 1.3, 0.77, 1.04) 0s;
    visibility: hidden;
    width: 110px;
}
.rcb_icon:hover .rcb_hiden {
    opacity: 1;
    transform: translateY(-18px);
    visibility: visible;
}
.rcb_icon .rcb_hiden span {
    background: rgba(0, 0, 0, 0.7);
    border-radius: 0 0 3px 3px;
    color: inherit;
    display: inline-block;
    padding: 5px 10px;
}


/* Левая часть */
.rcb_left {
    float: left;
    margin: 0 0 0 50px;
}
/* Меню слева START */
.pr_left_menu {
    display: inline-block;
    padding: 0 10px;
}
.pr_left_menu:hover{
	color: #d4d4d4;
	cursor: pointer;
}
.pr_left_menu:hover .rcb_menu {
	cursor: default;
    opacity: 1;
    transform: translateY(-18px);
    visibility: visible;
}
.rcb_menu {
    background: rgba(0, 0, 0, 0.7);
    border-top: 2px solid #e47641;
    display: block;
    left: 55px;
    opacity: 0;
    position: absolute;
    top: 54px;
    transform: translate3d(0px, 0px, 0px);
    transition: all 0.2s ease-in-out 0s;
    visibility: hidden;
	width: 180px;
}
#recallbar_new .rcb_menu ul {
    list-style: none;
    margin: 0;
    max-width: 200px;
    padding: 0;
}
#recallbar_new .rcb_menu li {
	list-style: none;
	padding: 10px;
}
#recallbar_new .rcb_menu li:hover {
    background: rgba(0, 0, 0, 0.3);
}
#recallbar_new .rcb_menu .menu-item-has-children {
	position: relative;
}
#recallbar_new .menu-item-has-children > a::after {
    color: inherit;
    content: "\f054";									/* fa-chevron-right */
    font-family: fontawesome;
    font-size: 11px;
    position: absolute;
    right: 3px;
    top: 13px;
}
#recallbar_new .rcb_menu .sub-menu {
    background: rgba(0, 0, 0, 0.7);
	left: 180px;
	opacity: 0;
    position: absolute;
    top: 0;
	transition: all 0.2s ease-in-out 0s;
	visibility: hidden;
    width: 100%;
}
#recallbar_new .rcb_menu .menu-item-has-children:hover .sub-menu {
	opacity: 1;
}
/* Меню слева END */
/* Левая часть END */


/* Правая часть START */
.rcb_right {
    float: right;
    margin: 0 50px 0 0;
}
.rcb_nmbr {
    background: #b2440f;
    border-radius: 50%;
    color: #f6f6f6;
    display: inline-block;
    font-size: 12px;
    height: 22px;
	line-height: 22px;
    margin: 2px 0 0;
    text-align: center;
    vertical-align: top;
    width: 22px;
}

/* Меню справа START */
.pr_menu {
    border-left: 2px solid transparent;
    box-sizing: unset;
    float: right;
    height: 36px;
    margin: 0 10px 0 30px;
    position: relative;
    width: 36px;
}
.pr_menu:hover {
    border-left: 2px solid #e47641;
	cursor: pointer;
}
.pr_menu .fa-ellipsis-h {
    color: #e47641;
    font-size: 22px;
    left: 10px;
    position: absolute;
    top: 14px;
    z-index: 2;
}
.pr_menu:hover .fa-ellipsis-h {
    display: none;
}
.pr_menu img {
	border: none;
    height: 36px;
    max-width: 36px;
    width: 36px;
}
.pr_sub_menu {
    background: rgba(0, 0, 0, 0.7);
    border-top: 2px solid #e47641;
    display: block;
    opacity: 0;
    position: absolute;
    right: 0;
    top: 54px;
    transform: translate3d(0px, 0px, 0px);
    transition: all 0.2s ease-in-out 0s;
    visibility: hidden;
    width: 200px;
}
.pr_menu:hover .pr_sub_menu {
	cursor: default;
    opacity: 1;
    transform: translateY(-18px);
    visibility: visible;
}
.rcb_line {
    padding: 2px 5px 3px 5px;
}
.rcb_line:hover {
    background: rgba(0, 0, 0, 0.3);
}
.pr_sub_menu .rcb_line .fa {
    color: #c8c8c8;
    font-size: 22px;
}
.rcb_line span {
    margin: 0 0 0 8px;
    vertical-align: text-top;
}

/* Меню справа END */
/* Правая часть END */
</style>



<?php } else { ?>
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
<?php } ?>