// Premium Three.js Background Effect - Enhanced Particles Network
document.addEventListener('DOMContentLoaded', () => {
    // Create container if not exists
    let container = document.getElementById('three-bg');
    if (!container) {
        container = document.createElement('div');
        container.id = 'three-bg';
        container.style.position = 'fixed';
        container.style.top = '0';
        container.style.left = '0';
        container.style.width = '100%';
        container.style.height = '100%';
        container.style.zIndex = '-1';
        container.style.pointerEvents = 'none';
        container.style.opacity = '0.7'; // Slightly more visible
        document.body.prepend(container);
    }

    // Scene setup
    const scene = new THREE.Scene();

    // Add ambient gradient fog for depth
    scene.fog = new THREE.FogExp2(0xffffff, 0.002);

    // Camera setup
    const camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
    camera.position.z = 40;

    // Renderer setup
    const renderer = new THREE.WebGLRenderer({
        alpha: true,
        antialias: true,
        powerPreference: "high-performance"
    });
    renderer.setSize(window.innerWidth, window.innerHeight);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2)); // Optimize for performance
    container.appendChild(renderer.domElement);

    // Create multiple particle systems with different colors
    const particleSystems = [];

    // System 1: Orange particles (primary)
    const createParticleSystem = (count, size, color, speed) => {
        const geometry = new THREE.BufferGeometry();
        const positions = new Float32Array(count * 3);
        const velocities = new Float32Array(count * 3);

        for (let i = 0; i < count * 3; i += 3) {
            positions[i] = (Math.random() - 0.5) * 100;
            positions[i + 1] = (Math.random() - 0.5) * 100;
            positions[i + 2] = (Math.random() - 0.5) * 100;

            // Random velocities for floating effect
            velocities[i] = (Math.random() - 0.5) * 0.02;
            velocities[i + 1] = (Math.random() - 0.5) * 0.02;
            velocities[i + 2] = (Math.random() - 0.5) * 0.02;
        }

        geometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));
        geometry.setAttribute('velocity', new THREE.BufferAttribute(velocities, 3));

        const material = new THREE.PointsMaterial({
            size: size,
            color: color,
            transparent: true,
            opacity: 0.8,
            blending: THREE.AdditiveBlending, // Glowing effect
            sizeAttenuation: true,
            vertexColors: false
        });

        const mesh = new THREE.Points(geometry, material);
        mesh.userData.speed = speed;
        return mesh;
    };

    // Create multiple layers with different colors
    particleSystems.push(createParticleSystem(500, 0.3, 0xFF8F00, 0.3)); // Orange
    particleSystems.push(createParticleSystem(300, 0.2, 0xFFCA28, 0.5)); // Golden
    particleSystems.push(createParticleSystem(200, 0.4, 0xFF6F00, 0.2)); // Deep Orange
    particleSystems.push(createParticleSystem(150, 0.15, 0xFFD54F, 0.7)); // Light Gold

    particleSystems.forEach(system => scene.add(system));

    // Add connecting lines between nearby particles
    const linesMaterial = new THREE.LineBasicMaterial({
        color: 0xFF8F00,
        transparent: true,
        opacity: 0.15,
        blending: THREE.AdditiveBlending
    });

    const linesGeometry = new THREE.BufferGeometry();
    const linePositions = [];
    const maxDistance = 10; // Connect particles within this distance

    function updateLines() {
        linePositions.length = 0;
        const positions = particleSystems[0].geometry.attributes.position.array;

        for (let i = 0; i < positions.length; i += 3) {
            for (let j = i + 3; j < positions.length; j += 3) {
                const dx = positions[i] - positions[j];
                const dy = positions[i + 1] - positions[j + 1];
                const dz = positions[i + 2] - positions[j + 2];
                const distance = Math.sqrt(dx * dx + dy * dy + dz * dz);

                if (distance < maxDistance) {
                    linePositions.push(positions[i], positions[i + 1], positions[i + 2]);
                    linePositions.push(positions[j], positions[j + 1], positions[j + 2]);
                }
            }
        }

        linesGeometry.setAttribute('position', new THREE.Float32BufferAttribute(linePositions, 3));
    }

    const linesMesh = new THREE.LineSegments(linesGeometry, linesMaterial);
    scene.add(linesMesh);

    // Mouse interaction
    let mouseX = 0;
    let mouseY = 0;
    let targetX = 0;
    let targetY = 0;

    document.addEventListener('mousemove', (event) => {
        mouseX = (event.clientX / window.innerWidth) * 2 - 1;
        mouseY = -(event.clientY / window.innerHeight) * 2 + 1;
    });

    // Animation Loop
    const clock = new THREE.Clock();
    let frameCount = 0;

    function animate() {
        requestAnimationFrame(animate);
        frameCount++;

        const elapsedTime = clock.getElapsedTime();

        // Smooth mouse following
        targetX += (mouseX - targetX) * 0.02;
        targetY += (mouseY - targetY) * 0.02;

        // Animate each particle system
        particleSystems.forEach((system, index) => {
            // Slower rotation for smoother effect
            system.rotation.y = elapsedTime * 0.03 * system.userData.speed;
            system.rotation.x = Math.sin(elapsedTime * 0.02) * 0.3;

            // Interactive movement
            system.rotation.y += targetX * 0.3;
            system.rotation.x += targetY * 0.3;

            // Wave motion
            const positions = system.geometry.attributes.position.array;
            const velocities = system.geometry.attributes.velocity.array;

            for (let i = 0; i < positions.length; i += 3) {
                // Floating animation
                positions[i] += velocities[i];
                positions[i + 1] += velocities[i + 1] + Math.sin(elapsedTime + i) * 0.001;
                positions[i + 2] += velocities[i + 2];

                // Boundary check - wrap around
                if (Math.abs(positions[i]) > 50) velocities[i] *= -1;
                if (Math.abs(positions[i + 1]) > 50) velocities[i + 1] *= -1;
                if (Math.abs(positions[i + 2]) > 50) velocities[i + 2] *= -1;
            }

            system.geometry.attributes.position.needsUpdate = true;
        });

        // Update connection lines every 3 frames (optimize performance)
        if (frameCount % 3 === 0) {
            updateLines();
        }

        // Pulse effect on camera
        camera.position.z = 40 + Math.sin(elapsedTime * 0.5) * 2;

        renderer.render(scene, camera);
    }

    animate();

    // Resize handler
    window.addEventListener('resize', () => {
        camera.aspect = window.innerWidth / window.innerHeight;
        camera.updateProjectionMatrix();
        renderer.setSize(window.innerWidth, window.innerHeight);
        renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    });

    // Clean up on page unload
    window.addEventListener('beforeunload', () => {
        particleSystems.forEach(system => {
            system.geometry.dispose();
            system.material.dispose();
        });
        linesGeometry.dispose();
        linesMaterial.dispose();
        renderer.dispose();
    });
});
