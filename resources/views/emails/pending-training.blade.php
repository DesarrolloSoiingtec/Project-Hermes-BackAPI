@component('mail::message')
<img src="https://www.clinicamedyser.com.co/wp-content/uploads/2022/02/clinica-medyser.png"
     alt="Clínica Medyser" style="display:block; margin:0 auto 20px; max-width:200px;">

# Cordial saludo {{ $firstName }} {{ $lastName }},

Se informa que existen **{{ $pendingCoursesCount }} capacitación{{ $pendingCoursesCount > 1 ? 'es' : '' }} pedagógica pendiente{{ $pendingCoursesCount > 1 ? 's' : '' }}** en el sistema.

Es importante tener presente que solo será posible solicitar una nueva cita **24 horas después de completar el curso**. Si no se realiza, la reprogramación quedará inhabilitada.

Se recomienda ingresar al siguiente enlace y completar el curso a la mayor brevedad:

@component('mail::button', ['url' => $trainingLink])
Acceder a la capacitación
@endcomponent

Ante cualquier inquietud, puede responder a este mensaje.

Saludos cordiales
**Medyser**
_Clinica oftalmológica_
@endcomponent
