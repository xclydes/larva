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
	<label class="radio-inline">
		<?= Form::radio($name, 1, $options['checked'], $options['attr']) ?>
		<?= trans(_XCLYDESLARVA_NS_RESOURCES_ . '::messages.true'); ?>
	</label>
	<label class="radio-inline">
		<?= Form::radio($name, 0, !$options['checked'], $options['attr']) ?>
		<?= trans(_XCLYDESLARVA_NS_RESOURCES_ . '::messages.false'); ?>
	</label>
    <?php //include 'help_block.php' ?>
<?php endif; ?>


<?php //include 'errors.php' ?>

<?php if ($showLabel && $showField): ?>
    <?php if ($options['wrapper'] !== false): ?>
    </div>
    <?php endif; ?>
<?php endif; ?>
