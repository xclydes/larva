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
                $isAgroup = $field->getOption('is_group', false);
                //Set the ratio
                $field->setOption('col_ratio', $colRatio);
                //Reset previous columns
                if( $isAgroup && $colIndex != 0 ) {
                    $colIndex = 0;
                    echo '</div>';
                }
                if( $colIndex == 0 ) {
                    $rowIndex++;
                    $grpClass = 'form-group';
                    if( $rowIndex > 4 ) {
                        $grpClass .= ' advanced';
                    }
                    echo '<div class="'.$grpClass.'">';
                }
                $colIndex++;

            ?>
        	<?= $field->render() ?>
            <?php
                if( $isAgroup || $colIndex == $columns ) {
                    $colIndex = 0;
                    echo '</div>';
                }
            ?>
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
