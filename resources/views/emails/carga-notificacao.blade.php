<x-mail::message>
@php
    $ordemFrete = $carga->ordem_frete
        ?? data_get($carga->dados_json, 'TransportationOrderID')
        ?? data_get($carga->dados_json, 'TransportationOrderId')
        ?? data_get($carga->dados_json, 'RootTransportationOrderID')
        ?? data_get($carga->dados_json, 'RootTransportationOrderId')
        ?? data_get($carga->dados_json, 'TorId')
        ?? data_get($carga->dados_json, 'TORId')
        ?? data_get($carga->dados_json, 'FreightOrderID')
        ?? data_get($carga->dados_json, 'FreightOrderId');
@endphp

@if ($modoTeste)
**Modo teste ativado** — por este motivo a carga **{{ $carga->rfq_id ?? $carga->id }}** não foi capturada no SAP (nenhuma chamada de aceite foi enviada).
@else
A carga **{{ $carga->rfq_id ?? $carga->id }}** foi **capturada** no SAP.
@endif

<x-mail::panel>
**Origem:** {{ $carga->origem ?? '—' }}  
**Destino:** {{ $carga->destino ?? '—' }}  
**Ordem de frete:** {{ $ordemFrete ?? '—' }}  
**Peso:** {{ $carga->peso ?? '—' }}  
**Distância:** {{ $carga->distancia ?? '—' }}
</x-mail::panel>

<x-mail::button :url="config('app.url')">
Abrir painel
</x-mail::button>

</x-mail::message>
