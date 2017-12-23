<?php
$columns = array_get( $formOptions, 'field_column_count', 1);
$rowOpen = array_get( $formOptions, 'field_row_open', '');
$rowClose = array_get( $formOptions, 'field_row_close', '');
?>
<?php if ($showStart): ?>
    <?= Form::open($formOptions) ?>
<?php endif; ?>

<?php if ($showFields): ?>

    <?php
    if( array_get( $formOptions, 'field_show_header', false) ) {
        echo $rowOpen;
        //Render the header actions
        echo $form->getHeaderActionContainer()->render();
        echo $rowClose;
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
                    echo $rowClose;
                }
                if( $colIndex == 0 ) {
                    $rowIndex++;
                    echo $rowOpen;
                }
                $colIndex++;
                echo $field->render();
                if( $isAgroup || $colIndex == $columns ) {
                    $colIndex = 0;
                    echo $rowClose;
                }
            }
        ?>
    <?php
        endforeach;
        echo $rowClose;
    ?>

    <?php
    if( array_get( $formOptions, 'field_show_footer', true) ) {
        echo $rowOpen;
            //Render the actions
            echo $form->getFooterActionContainer()->render();
        echo $rowClose;
    }
    ?>

<?php endif; ?>

<?php if ($showEnd): ?>
    <?= Form::close() ?>
<?php endif; ?>
