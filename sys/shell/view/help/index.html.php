

{{$description}}


<? if ($usage): ?>
Usage: {{$usage}}

<? endif; ?>

<? if (count($params)>0): ?>
Params
================================================================
<? foreach($params as $key => $param): ?>
{{$key}} - {{$param}}

<? endforeach; ?>
<? endif; ?>

<? if (count($switches)>0): ?>
Switches
================================================================
<? foreach($switches as $key => $switch): ?>
{{$key}} - {{$switch}}

<? endforeach; ?>
<? endif; ?>

