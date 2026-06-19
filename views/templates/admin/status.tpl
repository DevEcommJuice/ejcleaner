<div class="panel">
    <div class="panel-heading">
        <i class="icon-dashboard"></i> {l s='Estado de Tablas' mod='ejcleaner'}
        <span class="panel-heading-action">
            <button id="ejcleaner-btn-all" type="button" class="btn btn-success btn-sm" onclick="ejCleanerRun('all')">
                <i class="icon-play"></i> {l s='Limpiar Todo' mod='ejcleaner'}
            </button>
        </span>
    </div>

    <table class="table table-striped">
        <thead>
            <tr>
                <th>{l s='Opción' mod='ejcleaner'}</th>
                <th>{l s='Tablas' mod='ejcleaner'}</th>
                <th class="text-right">{l s='Filas (aprox.)' mod='ejcleaner'}</th>
                <th class="text-right">{l s='Tamaño' mod='ejcleaner'}</th>
                <th class="text-center">{l s='En Cron' mod='ejcleaner'}</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            {foreach $ejcleaner_groups as $group}
            <tr id="row-{$group.action}">
                <td><strong>{$group.label|escape:'html':'UTF-8'}</strong></td>
                <td><code style="font-size:11px;word-break:break-all">{$group.tables|escape:'html':'UTF-8'}</code></td>
                <td class="text-right">
                    {if $group.rows}
                        {$group.rows}
                    {else}
                        <span class="text-muted">—</span>
                    {/if}
                </td>
                <td class="text-right"><strong>{$group.size|escape:'html':'UTF-8'}</strong></td>
                <td class="text-center">
                    {if $group.enabled}
                        <span class="label label-success"><i class="icon-check"></i></span>
                    {else}
                        <span class="label label-default"><i class="icon-times"></i></span>
                    {/if}
                </td>
                <td class="text-right">
                    <button type="button"
                            class="btn btn-default btn-xs ejcleaner-item-btn"
                            onclick="ejCleanerRun('{$group.action|escape:'javascript'}')">
                        <i class="icon-trash"></i> {l s='Limpiar' mod='ejcleaner'}
                    </button>
                </td>
            </tr>
            {/foreach}
        </tbody>
    </table>

    <div id="ejcleaner-result" class="alert" style="display:none;margin-top:10px;margin-bottom:0"></div>
</div>

<script type="text/javascript">
var ejCleanerAjaxUrl = '{$ejcleaner_ajax_url|escape:'javascript'}';
var ejCleanerShopId = {$ejcleaner_shop_id|intval};

function ejCleanerRun(action) {
    var resultEl = document.getElementById('ejcleaner-result');
    var allBtns = document.querySelectorAll('.ejcleaner-item-btn, #ejcleaner-btn-all');

    if (!allBtns.length) return;

    allBtns = jQuery('.ejcleaner-item-btn, #ejcleaner-btn-all');
    allBtns.prop('disabled', true);

    resultEl.style.display = 'none';
    resultEl.className = 'alert';

    jQuery.ajax({
        url: ejCleanerAjaxUrl,
        type: 'POST',
        dataType: 'json',
        data: {
            ajax: 1,
            ejcleaner_action: action,
            id_shop: ejCleanerShopId
        },
        success: function(data) {
            resultEl.style.display = 'block';
            if (data.success) {
                resultEl.className = 'alert alert-success';
                resultEl.innerHTML = '<i class="icon-check"></i> <strong>OK:</strong> ' + data.message;
                setTimeout(function() {
                    location.reload();
                }, 2000);
            } else {
                resultEl.className = 'alert alert-danger';
                resultEl.innerHTML = '<i class="icon-times"></i> <strong>Error:</strong> ' + data.message;
                allBtns.prop('disabled', false);
            }
        },
        error: function() {
            resultEl.style.display = 'block';
            resultEl.className = 'alert alert-danger';
            resultEl.innerHTML = '<i class="icon-times"></i> <strong>Error:</strong> Error de comunicación con el servidor.';
            allBtns.prop('disabled', false);
        }
    });
}
</script>
