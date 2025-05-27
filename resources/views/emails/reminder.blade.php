@component('mail::message')
{{-- Logo Centrado --}}
<img src="https://www.clinicamedyser.com.co/wp-content/uploads/2022/02/clinica-medyser.png"
     alt="Clínica Medyser" style="display:block; margin:0 auto 20px; max-width:200px;">

# Hola {{ $firstName }} {{ $lastName }},

Vimos que no asististe a tu cita del **{{ $date }}**.
Para continuar con tu atención, debes _completar_ la **capacitación pedagógica**.

@component('mail::button', ['url' => $trainingLink])
Acceder a la capacitación
@endcomponent

Si tienes problemas con el enlace, dudas o inquietudes, responde este correo y te ayudamos!

Saludos cordiales,
**Medyser**
_Clinica oftalmológica_
@endcomponent
