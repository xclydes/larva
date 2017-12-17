@extends( config('larva.view.app') )


@section('content')
    <div class="row">
        <a href="{{ URL::route($routePrefix . '.index' ) }}" class="btn btn-primary"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span>&nbsp;{{ trans(_XCLYDESLARVA_NS_RESOURCES_ . '::buttons.cancel') }}</a>
    </div>
    <div class="row">
	    {!! form( $form ) !!}
    </div>
@endsection