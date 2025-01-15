<!DOCTYPE html>

 <head>
     <meta charset="UTF-8">
     <meta name="viewport" content="width=device-width, initial-scale=1.0">
     <meta http-equiv="X-UA-Compatible" content="ie=edge">
     <link href="https://unpkg.com/tailwindcss@^2/dist/tailwind.min.css" rel="stylesheet">
     <title>Code</title>
 </head>
 <html>
 
 <body>
     <section class="max-w-2xl px-6 py-8 mx-auto bg-white dark:bg-gray-900">
 
         <main class="mt-8">
             <h2 class="text-gray-700 dark:text-gray-200">Hi {{ $notifiable->name }},</h2>
 
             <p class="mt-2 leading-loose text-gray-600 dark:text-gray-300">
                 This is your code:
             </p>
 
             <div class="flex items-center mt-4 gap-x-4">
                     {{-- <p
                         class="flex items-center justify-center w-10 h-10 text-2xl font-medium text-blue-500 border border-blue-500 rounded-md dark:border-blue-400 dark:text-blue-400 ">
                         {{ $code[0] }}</p>
                     <p
                         class="flex items-center justify-center w-10 h-10 text-2xl font-medium text-blue-500 border border-blue-500 rounded-md dark:border-blue-400 dark:text-blue-400 ">
                         {{ $code[1] }}</p>
                     <p
                         class="flex items-center justify-center w-10 h-10 text-2xl font-medium text-blue-500 border border-blue-500 rounded-md dark:border-blue-400 dark:text-blue-400 ">
                         {{ $code[2] }}</p>
                     <p
                         class="flex items-center justify-center w-10 h-10 text-2xl font-medium text-blue-500 border border-blue-500 rounded-md dark:border-blue-400 dark:text-blue-400 ">
                         {{ $code[3] }}</p>
                     <p
                         class="flex items-center justify-center w-10 h-10 text-2xl font-medium text-blue-500 border border-blue-500 rounded-md dark:border-blue-400 dark:text-blue-400 ">
                         {{ $code[4] }}</p>
                     <p
                         class="flex items-center justify-center w-10 h-10 text-2xl font-medium text-blue-500 border border-blue-500 rounded-md dark:border-blue-400 dark:text-blue-400 ">
                         {{ $code[5] }}</p> --}}
             </div>
 
             <p class="mt-4 leading-loose text-gray-600 dark:text-gray-300">
                 This code will only be valid for the next 60 minutes.
             </p>
         </main>
 
 
         <footer class="mt-8">
             <p class="text-gray-500 dark:text-gray-400">
                 This email was sent to <a href="#" class="text-blue-600 hover:underline dark:text-blue-400"
                     target="_blank">{{ $notifiable->email }}</a>.
             </p>
             <p class="mt-3 text-gray-500 dark:text-gray-400">© 2023 SMS. All rights reserved..</p>
         </footer>
     </section>
 </body>
 
 </html>
 