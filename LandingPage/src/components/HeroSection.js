import React from 'react';
import { motion } from 'framer-motion';
import './HeroSection.css';

const HeroSection = () => {
  const customerAvatars = [
    'https://i.pravatar.cc/150?img=1',
    'https://i.pravatar.cc/150?img=2',
    'https://i.pravatar.cc/150?img=3',
  ];

  return (
    <section className="hero-section">
      <div className="hero-background">
        <div className="hero-gradient"></div>
        <div className="hero-pattern"></div>
      </div>

      <div className="container">
        <div className="hero-content">
          {/* Left Content */}
          <motion.div 
            className="hero-text"
            initial={{ opacity: 0, x: -50 }}
            animate={{ opacity: 1, x: 0 }}
            transition={{ duration: 0.8 }}
          >
            {/* Customer Badge */}
            <div className="customer-badge">
              <div className="customer-avatars">
                {customerAvatars.map((avatar, index) => (
                  <img 
                    key={index} 
                    src={avatar} 
                    alt={`Customer ${index + 1}`}
                    style={{ zIndex: customerAvatars.length - index }}
                  />
                ))}
              </div>
              <div className="customer-text">
                <span>Join 5k+ Customers</span>
                <button className="join-btn">Join today</button>
              </div>
            </div>

            <h1 className="hero-title">
              Effortless Payments and Secure Transactions— Built on{' '}
              <span className="gradient-text">trust</span>
            </h1>

            <p className="hero-description">
              Enjoy fee-free banking and earn cash back on your everyday purchases.
            </p>

            <div className="hero-cta">
              <a href="https://app.pointwave.ng/auth/register" className="btn btn-primary btn-lg">
                Open Account
              </a>
              <a href="https://app.pointwave.ng/auth/login" className="btn btn-secondary btn-lg">
                Sign In
              </a>
            </div>
          </motion.div>

          {/* Right Content - Dashboard Preview */}
          <motion.div 
            className="hero-image"
            initial={{ opacity: 0, x: 50 }}
            animate={{ opacity: 1, x: 0 }}
            transition={{ duration: 0.8, delay: 0.2 }}
          >
            <div className="dashboard-preview">
              <div className="dashboard-mockup">
                {/* Dashboard Header */}
                <div className="mockup-header">
                  <div className="mockup-logo"></div>
                  <div className="mockup-nav">
                    <div className="nav-item active"></div>
                    <div className="nav-item"></div>
                    <div className="nav-item"></div>
                  </div>
                </div>

                {/* Dashboard Content */}
                <div className="mockup-content">
                  <div className="balance-card">
                    <div className="balance-label"></div>
                    <div className="balance-amount"></div>
                    <div className="balance-actions">
                      <div className="action-btn primary"></div>
                      <div className="action-btn secondary"></div>
                    </div>
                  </div>

                  <div className="stats-grid">
                    <div className="stat-card blue"></div>
                    <div className="stat-card green"></div>
                    <div className="stat-card teal"></div>
                    <div className="stat-card orange"></div>
                  </div>
                </div>
              </div>

              {/* Floating Elements */}
              <motion.div 
                className="floating-card card-1"
                animate={{ y: [0, -20, 0] }}
                transition={{ duration: 3, repeat: Infinity, ease: "easeInOut" }}
              >
                <div className="card-icon green"></div>
                <div className="card-text">
                  <div className="card-title"></div>
                  <div className="card-value"></div>
                </div>
              </motion.div>

              <motion.div 
                className="floating-card card-2"
                animate={{ y: [0, -15, 0] }}
                transition={{ duration: 2.5, repeat: Infinity, ease: "easeInOut", delay: 0.5 }}
              >
                <div className="card-icon blue"></div>
                <div className="card-text">
                  <div className="card-title"></div>
                  <div className="card-value"></div>
                </div>
              </motion.div>
            </div>
          </motion.div>
        </div>
      </div>
    </section>
  );
};

export default HeroSection;
