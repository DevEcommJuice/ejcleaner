<div class="panel">
    <h3><i class="icon-link"></i> {l s='Tarea Programada (Cron)' mod='ejcleaner'}</h3>
    <div class="alert alert-warning">
        {l s='Copia esta URL en Plesk para ejecutar la limpieza según la configuración anterior:' mod='ejcleaner'}
    </div>
    <div class="input-group">
        <span class="input-group-addon"><i class="icon-terminal"></i></span>
        <input type="text" value="{$cron_url|escape:'html':'UTF-8'}" readonly onclick="this.select();" class="form-control">
    </div>
    <p class="help-block">
        {l s='Recomendado: Una vez al día (ej. 04:00 AM).' mod='ejcleaner'}
    </p>
    <hr>
    <div class="row">
        <div class="col-xs-12 text-center">
            <p>Desarrollado por <a href="https://www.ecommjuice.com" target="_blank"><strong>EcommJuice</strong></a></p>
        </div>
    </div>
</div>
