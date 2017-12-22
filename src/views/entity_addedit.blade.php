@extends( xclydes_larva_config('view.app') )


@section('content')
{!! xclydes_larva_config('edit.wrapper.open') !!}
	    {!! form( $form ) !!}
{!! xclydes_larva_config('edit.wrapper.close') !!}
@endsection