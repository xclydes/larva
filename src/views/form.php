<?php if ($showStart): ?>
    <?= Form::open($formOptions) ?>
<?php endif; ?>

<?php if ($showFields): ?>
    <?php
        $columns = 1;
        $rowOpen = '';
        $rowClose = '';
        if( is_array( $formOptions ) ) {
            $columns =  array_get( $formOptions, 'field_column_count', $columns);
            $rowOpen = array_get( $formOptions, 'field_row_open', $rowOpen);
            $rowClose = array_get( $formOptions, 'field_row_close', $rowClose);
        }
        $colIndex = 0;
        $rowIndex = 0;
        /** @var $field \Kris\LaravelFormBuilder\Fields\FormField */
    ?>
    <?php foreach ($fields as $field): ?>
    	<?php if( ! in_array($field->getName(), $exclude) ) { ?>
            <?php
                $isAgroup = $field->getOption('is_group', false);
                if( $isAgroup && $colIndex != 0 ) {
                    $colIndex = 0;
                    echo $rowClose;
                }
                if( $colIndex == 0 ) {
                    $rowIndex++;
                    echo $rowOpen;
                }
                $colIndex++;
            ?>
        	<?= $field->render() ?>
            <?php
                if( $isAgroup || $colIndex == $columns ) {
                    $colIndex = 0;
                    echo $rowClose;
                }
            ?>
		<?php } ?>
    <?php endforeach; ?>
<?php endif; ?>

<?php if ($showEnd): ?>
    <?= Form::close() ?>
<?php endif; ?>
