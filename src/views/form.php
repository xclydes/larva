<?php if ($showStart): ?>
    <?= Form::open($formOptions) ?>
<?php endif; ?>

<?php if ($showFields): ?>

    <?php
    if( array_get( $formOptions, 'field_show_header', false) ) {
        echo $field_row_open;
        //Render the header actions
        echo $form->getHeaderActionContainer()->render();
        echo $field_row_close;
    }
    ?>

    <?php
    $colIndex = 0;
    $rowIndex = 0;
    /** @var $field \Kris\LaravelFormBuilder\Fields\FormField */
    ?>
    <?php foreach ($fields as $field): ?>
        <?php
            if( ! in_array($field->getName(), $exclude) ) {
                $isAgroup = $field->getOption('is_group', false);
                if( $isAgroup && $colIndex != 0 ) {
                    $colIndex = 0;
                    echo $field_row_close;
                }
                if( $colIndex == 0 ) {
                    $rowIndex++;
                    echo $field_row_open;
                }
                $colIndex++;
                echo $field->render();
                if( $isAgroup || $colIndex == $field_column_count ) {
                    $colIndex = 0;
                    echo $field_row_close;
                }
            }
        ?>
    <?php
        endforeach;
        echo $field_row_close;
    ?>

    <?php
    if( array_get( $formOptions, 'field_show_footer', true) ) {
        echo $field_row_open;
            //Render the actions
            echo $form->getFooterActionContainer()->render();
        echo $field_row_close;
    }
    ?>

<?php endif; ?>

<?php if ($showEnd): ?>
    <?= Form::close() ?>
<?php endif; ?>
