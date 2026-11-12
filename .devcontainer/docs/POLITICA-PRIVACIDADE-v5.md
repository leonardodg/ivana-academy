# Política de Privacidade e Cookies, versão 5 (para o tema Ivana Academy v2)

Texto pronto para a política "Política de Privacidade e Cookies" do `tool_policy`
(*Administração do site › Usuários › Privacidade e políticas › Gerenciar políticas*).
Hoje na produção está a **v4** (id 4). Aplicar **só depois** de o tema `theme_ivana` estar ativo.

## O que muda em relação à v4

| v4 | v5 | Por quê |
|---|---|---|
| "Google Fonts/FontAwesome" como serviços de terceiros | sai | o tema v2 serve as fontes (Libre Caslon, Plus Jakarta Sans, JetBrains Mono) e os ícones do próprio servidor; nada é pedido ao Google para exibir a página |
| "(Tema Moove)" no texto em português | sai | o tema agora é o Ivana Academy v2 |
| não citava o reCAPTCHA | entra | o cadastro usa o Google reCAPTCHA (já estava ativo na v4, só não estava declarado) |
| "mudar a escolha limpando os dados deste site" | "Preferências de cookies", no rodapé | o rodapé novo reabre o aviso de cookies |
| links legais | entra a referência às políticas completas em courses.ivana.academy | o rodapé do Moodle agora leva às páginas legais da LP |

## Como aplicar

*Gerenciar políticas › Política de Privacidade e Cookies › Editar*, colar o **resumo** e o **texto
completo** abaixo (no editor, botão de código HTML `</>`), e escolher:

- **"Salvar como nova versão"** (recomendado): é a v5; o Moodle pede o aceite de novo a cada
  usuário no próximo login. É a escolha mais segura para a LGPD, porque o texto passa a declarar o
  reCAPTCHA.
- **"Alteração pequena"** (*minor change*): mantém os aceites da v4, sem pedir de novo. Só use se
  você considerar que nada material mudou.

Conferir depois: abrir o site numa janela anônima, o aviso de cookies deve trazer o link da
política, e `/admin/tool/policy/viewall.php` mostra a v5.

## Resumo (campo "Resumo")

```html
<p>We value your privacy. We collect only the data needed for your student account and progress. We use YouTube for course videos, Google reCAPTCHA to protect the sign-up form and, only with your consent, Google Analytics. By accepting, you agree to our data processing under LGPD (Brazil), Law 25.326 (Argentina) and GDPR.</p>
<p>Valoramos tu privacidad / Valorizamos a sua privacidade: ver el texto completo en español y português abajo.</p>
```

## Texto completo (campo "Política completa")

```html
<h3>Privacy Policy (English version)</h3>
<p><strong>1. Data collection:</strong></p>
<p>We process your name, email, IP address and learning progress to provide educational services.</p>
<p><strong>2. Third-party services:</strong></p>
<p>Course videos are played from YouTube, and the sign-up form is protected by Google reCAPTCHA. These services may process technical identifiers, such as your IP address, to work. The fonts and icons of the platform are served from our own server.</p>
<p><strong>3. Legal compliance:</strong></p>
<p>We comply with Brazil's LGPD, Argentina's Personal Data Protection Law (25.326) and international privacy standards.</p>
<p><strong>4. Your rights:</strong></p>
<p>You may access, correct or request the deletion of your data at any time from your profile settings or by writing to contato@ivana.academy (Brazil) or contacto@ivana.academy (Argentina).</p>
<p><strong>5. Cookies and Google Analytics:</strong></p>
<p>We use essential cookies for login and to keep your session. Google Analytics 4 analytics cookies are only used if you click "Accept" in the notice; they measure pages visited, time spent and device type, so we can improve the courses. We do not use advertising cookies or share data for ads. Your choice is saved in your browser, and you can change it at any time with "Cookie preferences" at the bottom of every page.</p>
<p>The full policies of Ivana Academy are also at <a href="https://courses.ivana.academy/pt-br/privacidade/">courses.ivana.academy/pt-br/privacidade</a> and <a href="https://courses.ivana.academy/es-ar/privacidad/">courses.ivana.academy/es-ar/privacidad</a>.</p>

<h3>Política de Privacidade (versão em português)</h3>
<p><strong>1. Coleta de dados:</strong></p>
<p>Processamos seu nome, e-mail, endereço IP e progresso nos cursos com a finalidade exclusiva de prestar os serviços educacionais.</p>
<p><strong>2. Serviços de terceiros:</strong></p>
<p>Os vídeos dos cursos são reproduzidos pelo YouTube, e o formulário de cadastro é protegido pelo Google reCAPTCHA. Esses serviços podem tratar dados técnicos, como o seu endereço IP, para funcionar. As fontes e os ícones da plataforma são servidos pelo nosso próprio servidor.</p>
<p><strong>3. Conformidade legal:</strong></p>
<p>Esta política atende à Lei Geral de Proteção de Dados (LGPD) do Brasil e às normas internacionais de privacidade.</p>
<p><strong>4. Seus direitos:</strong></p>
<p>Você pode acessar, corrigir ou pedir a exclusão dos seus dados a qualquer momento pelas configurações do seu perfil ou pelo e-mail contato@ivana.academy.</p>
<p><strong>5. Cookies e Google Analytics:</strong></p>
<p>Usamos cookies essenciais para o login e para manter a sua sessão. Cookies de análise do Google Analytics 4 só são usados se você clicar em "Aceitar" no aviso; eles medem páginas visitadas, tempo de permanência e tipo de dispositivo, para melhorarmos os cursos. Não usamos cookies de publicidade nem compartilhamos dados para anúncios. A sua escolha fica salva no seu navegador, e você pode mudá-la quando quiser em "Preferências de cookies", no rodapé de todas as páginas.</p>
<p>As políticas completas da Ivana Academy também estão em <a href="https://courses.ivana.academy/pt-br/privacidade/">courses.ivana.academy/pt-br/privacidade</a>.</p>

<h3>Política de Privacidad (versión en español)</h3>
<p><strong>1. Recolección de datos:</strong></p>
<p>Procesamos tu nombre, correo electrónico, dirección IP y progreso en los cursos con el fin de brindar servicios educativos.</p>
<p><strong>2. Servicios de terceros:</strong></p>
<p>Los videos de los cursos se reproducen desde YouTube, y el formulario de registro está protegido por Google reCAPTCHA. Estos servicios pueden procesar datos técnicos, como tu dirección IP, para funcionar. Las fuentes y los íconos de la plataforma se sirven desde nuestro propio servidor.</p>
<p><strong>3. Cumplimiento legal:</strong></p>
<p>Cumplimos con la Ley de Protección de Datos Personales de Argentina (25.326) y las normativas internacionales de privacidad.</p>
<p><strong>4. Tus derechos:</strong></p>
<p>Podés acceder, rectificar o pedir la supresión de tus datos en cualquier momento desde la configuración de tu perfil o escribiendo a contacto@ivana.academy.</p>
<p><strong>5. Cookies y Google Analytics:</strong></p>
<p>Usamos cookies esenciales para el inicio de sesión y para mantener tu sesión. Las cookies de análisis de Google Analytics 4 solo se usan si hacés clic en "Aceptar" en el aviso; miden páginas visitadas, tiempo de permanencia y tipo de dispositivo, para mejorar los cursos. No usamos cookies de publicidad ni compartimos datos para anuncios. Tu elección queda guardada en tu navegador, y podés cambiarla cuando quieras en "Preferencias de cookies", al pie de todas las páginas.</p>
<p>Las políticas completas de Ivana Academy también están en <a href="https://courses.ivana.academy/es-ar/privacidad/">courses.ivana.academy/es-ar/privacidad</a>.</p>
```
