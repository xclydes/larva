<?php if ($showStart): ?>
    <?= Form::open($formOptions) ?>
<?php endif; ?>

<?php if ($showFields): ?>
    <?php
        $columns = is_array( $formOptions ) ?
            array_get( $formOptions, 'field_column_count', 1) :
            1;
        $maxCols = xclydes_larva_config('edit.columns.max', 12);
        //Get the group class
        $grpClass = '';
        $colRatio = ($maxCols / ( $columns * 3 ));
        $colIndex = 0;
        $rowIndex = 0;
        /** @var $field \Kris\LaravelFormBuilder\Fields\FormField */
    ?>
    <?php foreach ($fields as $field): ?>
    	<?php if( ! in_array($field->getName(), $exclude) ) { ?>
            <?php
                //Set the ratio
                $field->setOption('col_ratio', $colRatio);
                //Update the wrapper options
                $wrapperAttrs = $field->getOption('wrapperAttrs', '');
                //Append the column size
                $wrapperAttrs .= 'class="col-sm-' . ($colRatio * 2) . '""';
                //Update it
                $field->setOption('wrapperAttrs', $wrapperAttrs);
            ?>
        	<?= $field->render() ?>
		<?php } ?>
    <?php endforeach; ?>
    <?php
        if( $colIndex != 0 ) {
            echo '</div>';
        }
    ?>
<?php endif; ?>

<?php if ($showEnd): ?>
    <?= Form::close() ?>
<?php endif; ?>
