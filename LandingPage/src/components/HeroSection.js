import React from 'react';
import { motion } from 'framer-motion';
import './HeroSection.css';

const HeroSection = () => {
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
            <h1 className="hero-title">
              Effortless Payments and <span className="hero-title-break">Secure Transactions</span>
              <span className="hero-title-subtitle">Built on <span className="gradient-text">trust</span></span>
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
                    <div className="balance-label">Total Balance</div>
                    <div className="balance-amount">₦2,450,000.00</div>
                    <div className="balance-actions">
                      <div className="action-btn primary">Send Money</div>
                      <div className="action-btn secondary">Add Funds</div>
                    </div>
                  </div>

                  <div className="stats-grid">
                    <div className="stat-card blue">
                      <div className="stat-content">
                        <div className="stat-label">Transactions</div>
                        <div className="stat-value">1,234</div>
                      </div>
                    </div>
                    <div className="stat-card green">
                      <div className="stat-content">
                        <div className="stat-label">Revenue</div>
                        <div className="stat-value">₦850K</div>
                      </div>
                    </div>
                    <div className="stat-card teal">
                      <div className="stat-content">
                        <div className="stat-label">Customers</div>
                        <div className="stat-value">5,678</div>
                      </div>
                    </div>
                    <div className="stat-card orange">
                      <div className="stat-content">
                        <div className="stat-label">Growth</div>
                        <div className="stat-value">+24%</div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              {/* Floating Elements */}
              <motion.div 
                className="floating-card card-1"
                animate={{ y: [0, -20, 0] }}
                transition={{ duration: 3, repeat: Infinity, ease: "easeInOut" }}
              >
                <div className="card-icon green">💰</div>
                <div className="card-text">
                  <div className="card-title">Payment Received</div>
                  <div className="card-value">+₦125,000</div>
                </div>
              </motion.div>

              <motion.div 
                className="floating-card card-2"
                animate={{ y: [0, -15, 0] }}
                transition={{ duration: 2.5, repeat: Infinity, ease: "easeInOut", delay: 0.5 }}
              >
                <div className="card-icon blue">📊</div>
                <div className="card-text">
                  <div className="card-title">New Customer</div>
                  <div className="card-value">John Doe</div>
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
