<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Artsly | Discover Visionary Artists & Artwork</title>
    <meta name="description" content="Artsly connects you with visionary artists pushing the boundaries of digital and traditional art. Discover and showcase incredible talent.">
    
    <!-- Styles -->
    <link rel="stylesheet" href="index.css">
    
    <!-- Favicon (Fallback to brand logo) -->
    <link rel="icon" type="image/jpeg" href="logo.jpg">
</head>
<body>

    <!-- Animated Background Blobs -->
    <div class="bg-blobs">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
    </div>

    <header>
        <nav class="navbar">
            <div class="logo">Artsly</div>
            <div class="auth-buttons">
                <a href="login.php" class="btn btn-secondary" id="signin-trigger">Sign In</a>
                <a href="register.php" class="btn btn-primary" id="signup-trigger">Sign Up</a>
            </div>
        </nav>
    </header>

    <main>
        <section class="hero">
            <!-- <div class="hero-content">
                <div class="logo-container">
                    <img src="images/logo.jpg" alt="Artsly Logo" class="brand-logo" onerror="this.src='https://placehold.co/200x200/0a0a0a/ffffff?text=Artsly'">
                </div> -->
                
                <h1>Discover the Soul <br>of Modern Art</h1>
                
                <p class="description">
                    Artsly is a premier destination that connects you with visionary artists pushing the
                    boundaries of digital and traditional art. A curated space for elite talent and passionate collectors.
                </p>

                <div class="hero-gallery">
                    <div class="hero-image-container side secondary left">
                        <img src="https://images.unsplash.com/photo-1526047932273-341f2a7631f9?q=80&w=600&auto=format&fit=crop&ar=3:4"
                            alt="Artistic Flower">
                    </div>
                    <div class="hero-image-container side primary left">
                        <img src="https://images.unsplash.com/photo-1472396961693-142e6e269027?q=80&w=600&auto=format&fit=crop&ar=3:4"
                            alt="Serene Place">
                    </div>
                    <div class="hero-image-container center">
                        <img src="artlify_hero_final.png" alt="Featured Art"
                            onerror="this.src='https://images.unsplash.com/photo-1579783902614-a3fb3927b6a5?q=80&w=1000&auto=format&fit=crop'">
                    </div>
                    <div class="hero-image-container side primary right">
                        <img src="https://images.unsplash.com/photo-1578301978693-85fa9c0320b9?q=80&w=600&auto=format&fit=crop&ar=3:4"
                            alt="Famous Painting">
                    </div>
                    <div class="hero-image-container side secondary right">
                        <img src="https://images.unsplash.com/photo-1583337130417-3346a1be7dee?q=80&w=600&auto=format&fit=crop&ar=3:4"
                            alt="Elegant Animal">
                    </div>
                </div>

            </div>
        </section>
    </main>



    <script>
        // Smooth hover effect for hero gallery
        document.querySelectorAll('.hero-image-container').forEach(card => {
            card.addEventListener('mousemove', (e) => {
                const rect = card.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                
                const centerX = rect.width / 2;
                const centerY = rect.height / 2;
                
                const rotateX = (y - centerY) / 10;
                const rotateY = (centerX - x) / 10;
                
                card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale(1.05)`;
            });
            
            card.addEventListener('mouseleave', () => {
                card.style.transform = `perspective(1000px) rotateX(0deg) rotateY(0deg) scale(1)`;
            });
        });

        // Search interaction
        const searchInput = document.getElementById('artist-search');
        searchInput.addEventListener('focus', () => {
            document.querySelector('.search-container').style.transform = 'scale(1.02)';
        });
        searchInput.addEventListener('blur', () => {
            document.querySelector('.search-container').style.transform = 'scale(1)';
        });
    </script>
    <footer><p>&copy; 2026 Artsly. By Saumya Chitrakar</p></footer>
</body>
</html>
