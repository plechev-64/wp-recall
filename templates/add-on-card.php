<?php global $addon,$active_addons;?>
<div class="plugin-card plugin-card-<?php echo $addon->slug; ?>">
    <div class="plugin-card-top">
        <a href="<?php echo $addon->add_on_uri; ?>" target="_blank" class="thickbox plugin-icon">
            <img src="<?php echo $addon->thumbnail; ?>">
        </a>
        <div class="name column-name">
            <h4><a href="<?php echo $addon->add_on_uri; ?>" class="thickbox"><?php echo $addon->name; ?></a></h4>
        </div>
        <div class="action-links">
            <ul class="plugin-action-buttons">
                <?php if(isset($active_addons[$addon->slug])): ?>
                    <li><span class="button button-disabled" title="Это дополнение уже установлено">Установлен</span></li>
                <?php else: ?>
                    <li><a class="button" target="_blank" data-slug="<?php echo $addon->slug; ?>" href="<?php echo $addon->add_on_uri; ?>" aria-label="Перейти на страницу <?php echo $addon->name; ?> <?php echo $addon->version; ?>" data-name="<?php echo $addon->name; ?> <?php echo $addon->version; ?>">Перейти</a></li>
                <?php endif; ?>
                <!--<li><a href="<?php echo $addon->add_on_uri; ?>" class="thickbox" aria-label="Подробности о <?php echo $addon->name; ?> <?php echo $addon->version; ?>" data-title="<?php echo $addon->add_on_uri; ?> <?php echo $addon->version; ?>">Детали</a></li>-->
            </ul>
        </div>
        <div class="desc column-description">
            <p><?php print_r($addon->description); ?></p>
            <p class="authors"> <cite>Автор: <a href="<?php echo $addon->author_uri; ?>" target="_blank" ><?php echo $addon->author; ?></a></cite></p>
        </div>
    </div>
    <div class="plugin-card-bottom">
        <!--<div class="vers column-rating">
            <div class="star-rating" title="Рейтинг 5,0 на основе 428 голосов">
                <span class="screen-reader-text">Рейтинг 5,0 на основе 428 голосов</span>
                <div class="star star-full"></div>
                <div class="star star-full"></div>
                <div class="star star-full"></div>
                <div class="star star-full"></div>
                <div class="star star-full"></div>
            </div>
            <span class="num-ratings">(428)</span>
        </div>-->
        <div class="column-updated">
            <strong>Обновление:</strong> <span title="<?php echo $addon->update; ?>">
                <?php echo human_time_diff(strtotime($addon->update),time() ).' назад'; ?>
            </span>
        </div>
        <div class="column-downloaded"><?php echo $addon->downloads; ?> загрузок</div>
        <div class="column-compatibility">
            <?php if($addon->support_core){ ?>
            <span class="compatibility-compatible"><strong>Совместим</strong> с Wp-Recall <?php echo $addon->support_core; ?> и выше</span>
            <?php }
             /* else{ ?>
                <span class="compatibility-compatible">Поддержка Wp-Recall вашей версии не гарантируется</span>
            <?php } */ ?>
        </div>
    </div>
</div>