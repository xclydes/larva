<?php if ($showLabel && $showField): ?>
    <?php if ($options['wrapper'] !== false): ?>
    <div <?= $options['wrapperAttrs'] ?> >
    <?php endif; ?>
<?php endif; ?>

<?php if ($showLabel && $options['label'] !== false && $options['label_show']): ?>
    <?php if ($options['is_child']): ?>
        <label <?= $options['labelAttrs'] ?>><?= $options['label'] ?></label>
    <?php else: ?>
        <?= Form::label($name, $options['label'], $options['label_attr']) ?>
    <?php endif; ?>
    &nbsp;
<?php endif; ?>

<?php if ($showField): ?>
    <?php
        //Render the children
        foreach($children as $child) {
            $child->render();
        }
    ?>
<?php endif; ?>

<?php if ($showLabel && $showField): ?>
    <?php if ($options['wrapper'] !== false): ?>
    </div>
    <?php endif; ?>
<?php endif; ?>
