<x-mail::message>
@if ($modoTeste)
**Modo teste ativado** — por este motivo a carga **{{ $carga->rfq_id ?? $carga->id }}** não foi capturada no SAP (nenhuma chamada de aceite foi enviada).
@else
A carga **{{ $carga->rfq_id ?? $carga->id }}** foi **capturada** no SAP.
@endif

<x-mail::panel>
**Origem:** {{ $carga->origem ?? '—' }}  
**Destino:** {{ $carga->destino ?? '—' }}  
**Peso:** {{ $carga->peso ?? '—' }}  
**Distância:** {{ $carga->distancia ?? '—' }}
</x-mail::panel>

<x-mail::button :url="config('app.url')">
Abrir painel
</x-mail::button>

</x-mail::message>
