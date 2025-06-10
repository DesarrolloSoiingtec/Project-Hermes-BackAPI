<x-mail::message>
<img src="https://www.clinicamedyser.com.co/wp-content/uploads/2022/02/clinica-medyser.png"
     alt="Clínica Medyser" style="display:block; margin:0 auto 20px; max-width:200px;">

# Estimado/a {{ $firstName }} {{ $lastName }},

Se informa que existen <strong>{{ $pendingCoursesCount }} capacitación{{ $pendingCoursesCount > 1 ? 'es' : '' }} pendiente{{ $pendingCoursesCount > 1 ? 's' : '' }}</strong>.

Es importante tener presente que solo será posible solicitar una nueva cita <strong>24 horas después de completarla.</strong>.

Le invitamos a acceder al siguiente enlace para completar su capacitación lo antes posible. Esta es requisito indispensable para continuar con su proceso de atención:<br>
_→ Si recibió este correo por error o ya completó el curso, puede ignorarlo. Disculpe las molestias_

<x-mail::button :url="$trainingLink">
Acceder a la capacitación
</x-mail::button>

Ante cualquier inquietud, puede responder a este mensaje.

<hr style="border: 1px solid #e8e8e8; margin: 20px 0;">

Atentamente,<br>
**MEDYSER IPS SAS** <br>
_Clínica oftalmológica_

</x-mail::message>
