<div class="panel">
    <div class="panel-heading">
        <i class="icon-cogs"></i> {l s='Configuración del Cron' mod='ejcleaner'}
    </div>
    
    <div class="alert alert-info">
        <p>{l s='Configura esta URL en Plesk (Tarea Programada -> Obtener URL) para automatizar la limpieza.' mod='ejcleaner'}</p>
    </div>

    <div class="form-group">
        <label class="control-label col-lg-3">{l s='URL del Cron' mod='ejcleaner'}</label>
        <div class="col-lg-9">
            <input type="text" value="{$cron_url|escape:'html':'UTF-8'}" readonly class="form-control" onclick="this.select();">
            <p class="help-block">{l s='Ejecución recomendada: 1 vez al día (ej. 04:00 AM).' mod='ejcleaner'}</p>
        </div>
    </div>
    <div class="clearfix"></div>
</div>

<div class="panel">
    <div class="panel-heading">
        <i class="icon-info"></i> {l s='Información' mod='ejcleaner'}
    </div>
    <p><strong>Autor:</strong> EcommJuice (<a href="https://www.ecommjuice.com/" target="_blank">www.ecommjuice.com</a>)</p>
    <p>{l s='Este módulo vacía las tablas de conexiones y los directorios de caché de Symfony/Smarty.' mod='ejcleaner'}</p>
</div>
