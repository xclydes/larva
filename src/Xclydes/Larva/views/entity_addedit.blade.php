@extends( config('larva.view.app') )


@section('content')
    <div class="row">
	    {!! form( $form ) !!}
    </div>
@endsection