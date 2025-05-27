@component('mail::message')
{{-- Logo --}}
<img src="https://www.clinicamedyser.com.co/wp-content/uploads/2022/02/clinica-medyser.png"
     alt="Clínica Medyser" style="display:block; margin:0 auto 20px; max-width:200px;">

# ¡Felicidades {{ $firstName }} {{ $lastName }}!

Ha completado con éxito la capacitación pedagógica en Clínica Medyser. A partir de este momento, puede agendar nuevamente su cita médica. Tenga en cuenta que, en caso de no asistir a la cita programada, deberá retomar la capacitación.

Si tiene inconvenientes con el enlace o alguna duda, responda a este correo para brindarle asistencia.

Conserve este mensaje como comprobante de su capacitación.

{{-- Botón --}}

@component('mail::button', ['url' => $trainingLink])
Acceder al portal
@endcomponent

Saludos cordiales
**Medyser**  
_Clinica oftalmológica_
@endcomponent
