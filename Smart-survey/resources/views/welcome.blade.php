@extends('layouts.app')

@section('content')
<div class="flex flex-col lg:flex-row items-center justify-between min-h-[70vh] gap-16">

    <!-- Left: Content -->
    <div class="flex-1 text-center lg:text-left order-2 lg:order-1">
        <div class="inline-block px-4 py-1.5 rounded-full bg-white/10 border border-white/20 text-white text-[10px] font-black tracking-widest uppercase mb-6 shadow-sm">
            Engineering Excellence
        </div>
        <h1 class="text-slate-900 text-4xl md:text-6xl font-extrabold mb-8 leading-[1.05] tracking-tighter drop-shadow-sm">
            Engineering <br><span class="text-deepteal">The Future</span> <br>of Land.
        </h1>
        <p class="text-slate-800 text-lg md:text-xl max-w-xl mb-12 font-semibold leading-relaxed opacity-90">
            Professional boundary surveys, topographic mapping, and construction staking with precision and reliability you can trust.
        </p>

        <!-- CTAs -->
        <div class="flex flex-col sm:flex-row gap-5 justify-center lg:justify-start">
            @auth
                <a href="{{ route('survey.create') }}" class="btn-teal px-10 py-5 rounded-2xl text-white font-black text-lg shadow-xl flex items-center justify-center gap-3 group">
                    Book a New Survey
                    <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                </a>
                <a href="{{ route('survey.index') }}" class="glass border-white/30 px-10 py-5 rounded-2xl text-slate-900 font-black text-lg shadow-lg hover:bg-white/40 transition-all">
                    View Progress
                </a>
            @else
                <a href="{{ route('register') }}" class="btn-teal px-10 py-5 rounded-2xl text-white font-black text-lg shadow-xl">
                    Get Started Now
                </a>
                <a href="{{ route('login') }}" class="glass border-white/30 px-10 py-5 rounded-2xl text-slate-900 font-black text-lg shadow-lg hover:bg-white/40 transition-all">
                    Client Login
                </a>
            @endauth
        </div>
    </div>

    <!-- Right: Large Circle Video Avatar -->
    <div class="flex-1 flex justify-center lg:justify-end order-1 lg:order-2">
        <div class="relative group">
            <!-- Outer glow ring -->
            <div class="absolute inset-0 rounded-full bg-white/30 blur-[50px] scale-110 group-hover:bg-white/40 transition-all duration-700"></div>

            <!-- Decorative ring -->
            <div class="absolute inset-[-20px] rounded-full border-2 border-white/20 animate-spin" style="animation-duration: 20s;"></div>
            <div class="absolute inset-[-40px] rounded-full border border-white/10 animate-spin" style="animation-duration: 35s; animation-direction: reverse;"></div>

            <!-- Main Circle Video Container -->
            <div class="relative w-[240px] h-[240px] md:w-[350px] md:h-[350px] rounded-full overflow-hidden border-[10px] border-white shadow-[0_0_60px_rgba(255,255,255,0.3)] z-10 transition-all duration-700 hover:scale-[1.03]">
                <video
                    src="{{ asset('videos/video.mp4') }}"
                    autoplay loop muted playsinline
                    class="w-full h-full object-cover object-center scale-105"
                ></video>
            </div>

            <!-- Floating Badge -->
            <div class="absolute -bottom-4 left-1/2 -translate-x-1/2 glass px-8 py-3 rounded-2xl border border-white/30 shadow-xl z-20 whitespace-nowrap">
                <div class="flex items-center gap-3">
                    <div class="w-2.5 h-2.5 rounded-full bg-green-400 animate-pulse shadow-[0_0_10px_#4ade80]"></div>
                    <span class="text-slate-900 font-black text-sm tracking-wide">Lead Surveyor — Online</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Services Section -->
<section id="services" class="mt-32 pt-16">
    <div class="text-center mb-12">
        <h2 class="text-3xl md:text-4xl font-extrabold text-slate-900 tracking-tighter mb-4">Our Professional <span class="text-deepteal">Services</span></h2>
        <p class="text-slate-800 font-semibold max-w-2xl mx-auto opacity-80">Comprehensive land surveying solutions tailored to your specific needs, combining traditional expertise with modern precision.</p>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <!-- Service 1 -->
        <div class="glass p-8 rounded-3xl border border-white/20 shadow-lg hover:shadow-xl transition-all group">
            <div class="w-14 h-14 bg-deepteal/10 rounded-2xl flex items-center justify-center mb-6 text-deepteal group-hover:scale-110 transition-transform">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"></path></svg>
            </div>
            <h3 class="text-xl font-bold text-slate-900 mb-3">Boundary Surveys</h3>
            <p class="text-sm font-semibold text-slate-700 opacity-90 leading-relaxed">Accurate determination of property lines and corners for residential, commercial, and rural lands to resolve disputes or prepare for new developments.</p>
        </div>
        
        <!-- Service 2 -->
        <div class="glass p-8 rounded-3xl border border-white/20 shadow-lg hover:shadow-xl transition-all group">
            <div class="w-14 h-14 bg-deepteal/10 rounded-2xl flex items-center justify-center mb-6 text-deepteal group-hover:scale-110 transition-transform">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path></svg>
            </div>
            <h3 class="text-xl font-bold text-slate-900 mb-3">Topographic Mapping</h3>
            <p class="text-sm font-semibold text-slate-700 opacity-90 leading-relaxed">Detailed mapping of natural and man-made features, including elevations and contours, essential for architectural design and civil engineering.</p>
        </div>
        
        <!-- Service 3 -->
        <div class="glass p-8 rounded-3xl border border-white/20 shadow-lg hover:shadow-xl transition-all group">
            <div class="w-14 h-14 bg-deepteal/10 rounded-2xl flex items-center justify-center mb-6 text-deepteal group-hover:scale-110 transition-transform">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
            </div>
            <h3 class="text-xl font-bold text-slate-900 mb-3">Land Subdivision</h3>
            <p class="text-sm font-semibold text-slate-700 opacity-90 leading-relaxed">Expert planning and division of large land parcels into smaller lots, ensuring full compliance with local government regulations and zoning laws.</p>
        </div>
    </div>
</section>

<!-- About Us Section -->
<section id="about" class="mt-32 pt-16">
    <div class="glass p-10 md:p-14 rounded-[3rem] border border-white/30 shadow-xl relative overflow-hidden">
        <!-- Background accents -->
        <div class="absolute top-0 right-0 w-64 h-64 bg-white/20 blur-[80px] rounded-full"></div>
        
        <div class="relative z-10 flex flex-col md:flex-row gap-12 items-center">
            <div class="flex-1 text-center md:text-left">
                <div class="inline-block px-4 py-1.5 rounded-full bg-deepteal/10 text-deepteal text-[10px] font-black tracking-widest uppercase mb-4">
                    About The Surveyor
                </div>
                <h2 class="text-3xl md:text-4xl font-extrabold text-slate-900 tracking-tighter mb-6">Expertise You Can Trust.</h2>
                <p class="text-slate-800 font-semibold mb-6 leading-relaxed">
                    Led by <span class="font-extrabold text-deepteal">A.S.K Paranage</span>, a highly experienced Registered Licensed Surveyor, Smart Survey Systems is dedicated to providing the highest standard of precision in land surveying. 
                </p>
                <p class="text-slate-800 font-semibold leading-relaxed mb-8">
                    With a deep understanding of local land laws and advanced surveying techniques, we ensure that every project is executed with flawless accuracy and legal integrity.
                </p>
                
                <div class="inline-flex items-center gap-3 bg-white/40 px-5 py-3 rounded-2xl border border-white/50">
                    <svg class="w-6 h-6 text-deepteal" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                    <div>
                        <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Registered License No.</p>
                        <p class="text-lg font-extrabold text-slate-900">20001019</p>
                    </div>
                </div>
            </div>
            
            <div class="md:w-[350px] shrink-0">
                <div class="relative rounded-3xl overflow-hidden shadow-2xl border-4 border-white/50 aspect-[4/5] bg-slate-200">
                    <!-- Placeholder or another image can go here if provided, keeping it simple for now -->
                    <img src="https://images.unsplash.com/photo-1541888081198-5c1eb53550ce?q=80&w=800&auto=format&fit=crop" class="w-full h-full object-cover grayscale-[30%] opacity-90" alt="Surveying Equipment">
                    <div class="absolute inset-0 bg-gradient-to-t from-deepteal/80 to-transparent flex items-end p-6">
                        <p class="text-white font-extrabold text-xl">Precision in every measurement.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Contact Section -->
<section id="contact" class="mt-32 pt-16 mb-20">
    <div class="text-center mb-12">
        <h2 class="text-3xl md:text-4xl font-extrabold text-slate-900 tracking-tighter mb-4">Get In <span class="text-deepteal">Touch</span></h2>
        <p class="text-slate-800 font-semibold max-w-xl mx-auto opacity-80">Ready to start your project? Contact us today for professional advice or to request a detailed quotation.</p>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-4xl mx-auto">
        <!-- Call -->
        <div class="glass p-6 text-center rounded-3xl border border-white/20 shadow-md">
            <div class="w-12 h-12 bg-white/40 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-800">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
            </div>
            <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Direct Call</p>
            <p class="text-lg font-bold text-slate-900">037 22 64 605</p>
        </div>
        
        <!-- WhatsApp -->
        <div class="glass p-6 text-center rounded-3xl border border-[#25D366]/20 shadow-md">
            <div class="w-12 h-12 bg-[#25D366]/10 rounded-full flex items-center justify-center mx-auto mb-4 text-[#25D366]">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12.012 2c-5.506 0-9.989 4.478-9.99 9.984a9.964 9.964 0 001.333 4.976L2 22l5.187-1.359a9.922 9.922 0 004.825 1.25c5.506 0 9.989-4.479 9.989-9.985S17.518 2 12.012 2zm0 16.5c-1.525 0-3.021-.403-4.331-1.166l-.311-.182-3.218.844.858-3.136-.2-.315A8.349 8.349 0 013.633 11.98c0-4.622 3.766-8.384 8.379-8.384 4.613 0 8.379 3.762 8.379 8.384s-3.766 8.384-8.379 8.384zm4.6-6.287c-.252-.126-1.492-.736-1.724-.821-.232-.084-.401-.126-.569.126-.169.252-.653.821-.801.989-.148.169-.296.189-.548.063-1.124-.564-2.193-1.3-2.915-2.226-.188-.242.188-.225.68-.707.126-.126.252-.252.379-.4.126-.147.169-.252.252-.42.084-.169.042-.316-.021-.442-.063-.126-.569-1.368-.779-1.874-.206-.496-.416-.428-.569-.436-.148-.008-.317-.008-.485-.008-.169 0-.443.063-.675.316-.232.252-.886.863-.886 2.105s.907 2.442 1.034 2.61c.126.168 1.777 2.716 4.305 3.805.6.258 1.069.412 1.436.527.603.19 1.152.163 1.585.099.486-.072 1.492-.61 1.703-1.199.211-.589.211-1.094.148-1.199-.064-.105-.233-.168-.486-.295z"></path></svg>
            </div>
            <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">WhatsApp</p>
            <p class="text-lg font-bold text-slate-900">076 790 63 55</p>
        </div>
        
        <!-- Address -->
        <div class="glass p-6 text-center rounded-3xl border border-white/20 shadow-md md:col-span-1 col-span-1">
            <div class="w-12 h-12 bg-white/40 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-800">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
            </div>
            <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Office Address</p>
            <p class="text-sm font-bold text-slate-900 leading-snug max-w-[200px] mx-auto">129/14, Temple Road,<br>Hiripitiya, Nikadalupotha</p>
        </div>
    </div>
</section>

@endsection
