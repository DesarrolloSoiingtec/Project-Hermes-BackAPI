<x-mail::message>
{{-- Logo --}}
<img src="https://www.clinicamedyser.com.co/wp-content/uploads/2022/02/clinica-medyser.png"
     alt="Clínica Medyser" style="display:block; margin:0 auto 20px; max-width:200px;">

# Estimado/a {{ $firstName }} {{ $lastName }},

**¡Felicitaciones!** Nos complace informarle que ha completado exitosamente la capacitación pedagógica asignada.

<hr style="border: 1px solid #e8e8e8; margin: 20px 0;">

📋 **DATOS DE CERTIFICACIÓN:** <br>
Número de documento: **{{ $documentNumber }}** <br>
Curso completado: **{{ $courseName }}** <br>
Fecha de asignación: **{{ $startDate }}** <br>
Fecha de finalización: **{{ $completionDate }}** <br>

Su compromiso con la puntualidad y asistencia a las citas contribuye a un sistema de salud más eficiente para todos.<br>

<hr style="border: 1px solid #e8e8e8; margin: 20px 0;">

⏰ **PRÓXIMOS PASOS:** <br>
**Importante:** 24 horas después de recibir este correo, podrá solicitar nuevamente su cita médica a través de nuestros canales habituales.

<hr style="border: 1px solid #e8e8e8; margin: 20px 0;">

**Este correo sirve como evidencia oficial de la finalización de su capacitación pedagógica.**
Si tiene alguna pregunta o necesita asistencia para agendar su nueva cita, no dude en contactarnos respondiendo a este correo o ingresando a nuestro portal.

{{-- Button --}}

<x-mail::button :url="$trainingLink">
    Acceder al Portal
</x-mail::button>


Atentamente,<br>
**MEDYSER IPS SAS** <br>
_Clínica oftalmológica_

</x-mail::message>
