<div class="panel">
    <h3><i class="icon-link"></i> {l s='Tarea Programada (Cron)' mod='ejcleaner'}</h3>
    
    <div class="alert alert-info">
        {l s='Copia esta URL en tu Plesk (Tareas Programadas -> Obtener URL) para automatizar la limpieza según las opciones que hayas marcado arriba.' mod='ejcleaner'}
    </div>
    
    <div class="input-group">
        <span class="input-group-addon"><i class="icon-terminal"></i></span>
        <input type="text" value="{$cron_url|escape:'html':'UTF-8'}" readonly onclick="this.select();" class="form-control">
    </div>
    
    <p class="help-block">
        {l s='Ejecución recomendada: 1 vez al día (por ejemplo, a las 03:30 AM o 04:00 AM).' mod='ejcleaner'}
    </p>
    <hr>
    <div class="row">
        <div class="col-xs-12 text-center">
            <p>Desarrollado por <a href="https://www.ecommjuice.com" target="_blank"><strong>EcommJuice</strong></a></p>
        </div>
    </div>
</div>
