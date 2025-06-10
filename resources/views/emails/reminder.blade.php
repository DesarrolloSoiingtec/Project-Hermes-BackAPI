<x-mail::message>
{{-- Logo Centrado --}}
<img src="https://www.clinicamedyser.com.co/wp-content/uploads/2022/02/clinica-medyser.png"
     alt="Clínica Medyser" style="display:block; margin:0 auto 20px; max-width:200px;">


# Estimado/a {{ $firstName }} {{ $lastName }},

Esperamos que se encuentre muy bien. Hemos notado que no pudo asistir a su cita de especialidad **{{ $specialty }}**, signada con el Dr./la Dra. **{{ $medical }}**, programada para el día: **{{ $date }}** a las **{{ $hour }}**. Para poder reagendar su consulta o continuar con su proceso de atención, es necesario que complete la capacitación pedagógica asignada: **{{ $course }}**. Una vez finalizada la capacitación, deberá esperar 24 horas para solicitar nuevamente su cita médica.

Entendemos que a veces surgen imprevistos, pero este curso le brindará las herramientas necesarias para prepararse mejor y, de ese modo, tanto usted como nuestro equipo podamos aprovechar al máximo su próxima consulta.

A continuación encontrará el botón para ingresar a la capacitación:<br>
_→ Si recibió este correo por error o ya completó el curso, puede ignorarlo. Disculpe las molestias_
{{-- Botón de Capacitación --}}

<x-mail::button :url="$trainingLink">
    Acceder a la capacitación
</x-mail::button>

Si tienes problemas con el enlace, dudas o inquietudes, responde este correo y te ayudamos!

<hr style="border: 1px solid #e8e8e8; margin: 20px 0;">

Atentamente,<br>
**MEDYSER IPS SAS** <br>
_Clínica oftalmológica_

</x-mail::message>
