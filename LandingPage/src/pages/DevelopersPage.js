import React from 'react';
import Navbar from '../components/Navbar';
import Footer from '../components/Footer';
import './DevelopersPage.css';

const DevelopersPage = () => {
  const features = [
    {
      icon: '📚',
      title: 'Comprehensive Docs',
      description: 'Clear, detailed documentation with code examples'
    },
    {
      icon: '🔌',
      title: 'RESTful APIs',
      description: 'Clean, intuitive API endpoints that just work'
    },
    {
      icon: '🔐',
      title: 'Secure Authentication',
      description: 'API keys and webhook signatures for security'
    },
    {
      icon: '🧪',
      title: 'Sandbox Environment',
      description: 'Test your integration without real transactions'
    }
  ];

  const quickStart = [
    {
      step: '1',
      title: 'Create Account',
      description: 'Sign up and get your API keys instantly'
    },
    {
      step: '2',
      title: 'Read Docs',
      description: 'Explore our comprehensive API documentation'
    },
    {
      step: '3',
      title: 'Integrate',
      description: 'Add PointWave to your application'
    },
    {
      step: '4',
      title: 'Go Live',
      description: 'Start accepting payments from customers'
    }
  ];

  return (
    <div className="developers-page">
      <Navbar />
      <main>
        <section className="developers-hero">
          <div className="container">
            <h1 className="page-title">Built for Developers</h1>
            <p className="page-subtitle">
              Simple, powerful APIs to integrate payments into your application
            </p>
            <div className="hero-buttons">
              <a 
                href="https://app.pointwave.ng/documentation/home" 
                className="btn btn-primary btn-large"
                target="_blank"
                rel="noopener noreferrer"
              >
                View Documentation
              </a>
              <a 
                href="https://app.pointwave.ng/auth/register" 
                className="btn btn-outline btn-large"
              >
                Get API Keys
              </a>
            </div>
          </div>
        </section>

        <section className="developers-features">
          <div className="container">
            <h2 className="section-title">Developer-Friendly Features</h2>
            <div className="features-grid">
              {features.map((feature, index) => (
                <div key={index} className="feature-card">
                  <div className="feature-icon">{feature.icon}</div>
                  <h3 className="feature-title">{feature.title}</h3>
                  <p className="feature-description">{feature.description}</p>
                </div>
              ))}
            </div>
          </div>
        </section>

        <section className="quick-start">
          <div className="container">
            <h2 className="section-title">Quick Start Guide</h2>
            <div className="steps-grid">
              {quickStart.map((item, index) => (
                <div key={index} className="step-card">
                  <div className="step-number">{item.step}</div>
                  <h3 className="step-title">{item.title}</h3>
                  <p className="step-description">{item.description}</p>
                </div>
              ))}
            </div>
          </div>
        </section>

        <section className="code-example">
          <div className="container">
            <h2 className="section-title">Simple Integration</h2>
            <div className="code-box">
              <div className="code-header">
                <span className="code-lang">JavaScript</span>
              </div>
              <pre className="code-content">
{`// Initialize PointWave
const pointwave = require('pointwave-node');
pointwave.setApiKey('your_api_key');

// Create virtual account
const account = await pointwave.virtualAccounts.create({
  customer_name: "John Doe",
  customer_email: "john@example.com",
  bvn: "22222222222"
});

console.log(account.account_number);`}
              </pre>
            </div>
            <div className="code-cta">
              <a 
                href="https://app.pointwave.ng/documentation/home" 
                className="btn btn-primary"
                target="_blank"
                rel="noopener noreferrer"
              >
                Explore Full Documentation
              </a>
            </div>
          </div>
        </section>
      </main>
      <Footer />
    </div>
  );
};

export default DevelopersPage;
