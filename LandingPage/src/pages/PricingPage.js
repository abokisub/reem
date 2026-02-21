import React from 'react';
import Navbar from '../components/Navbar';
import Footer from '../components/Footer';
import './PricingPage.css';

const PricingPage = () => {
  const plans = [
    {
      name: 'Starter',
      price: 'Free',
      description: 'Perfect for testing and small projects',
      features: [
        'Virtual Account Creation',
        'Basic API Access',
        'Webhook Support',
        'Sandbox Environment',
        'Email Support',
        'Standard Processing Speed'
      ],
      cta: 'Get Started',
      link: 'https://app.pointwave.ng/auth/register',
      popular: false
    },
    {
      name: 'Business',
      price: 'Custom',
      description: 'For growing businesses with higher volume',
      features: [
        'Everything in Starter',
        'Priority Support',
        'Custom Integration Help',
        'Advanced Analytics',
        'Dedicated Account Manager',
        'Custom Settlement Schedule'
      ],
      cta: 'Contact Sales',
      link: '/support',
      popular: true
    },
    {
      name: 'Enterprise',
      price: 'Custom',
      description: 'For large organizations with specific needs',
      features: [
        'Everything in Business',
        '24/7 Phone Support',
        'Custom SLA',
        'White-label Options',
        'On-premise Deployment',
        'Custom Features Development'
      ],
      cta: 'Contact Sales',
      link: '/support',
      popular: false
    }
  ];

  const fees = [
    {
      service: 'Virtual Account Creation',
      fee: '0.7% (capped at ₦700)',
      description: 'Per transaction received'
    },
    {
      service: 'Bank Transfer',
      fee: 'Variable',
      description: 'Based on transfer type and amount'
    },
    {
      service: 'KYC Verification',
      fee: 'Per service',
      description: 'BVN, NIN, CAC verification'
    }
  ];

  return (
    <div className="pricing-page">
      <Navbar />
      <main>
        <section className="pricing-hero">
          <div className="container">
            <h1 className="page-title">Simple, Transparent Pricing</h1>
            <p className="page-subtitle">
              No hidden fees. Pay only for what you use.
            </p>
          </div>
        </section>

        <section className="pricing-plans">
          <div className="container">
            <div className="plans-grid">
              {plans.map((plan, index) => (
                <div 
                  key={index} 
                  className={`plan-card ${plan.popular ? 'popular' : ''}`}
                >
                  {plan.popular && <div className="popular-badge">Most Popular</div>}
                  <h3 className="plan-name">{plan.name}</h3>
                  <div className="plan-price">{plan.price}</div>
                  <p className="plan-description">{plan.description}</p>
                  <ul className="plan-features">
                    {plan.features.map((feature, idx) => (
                      <li key={idx}>
                        <span className="check-icon">✓</span>
                        {feature}
                      </li>
                    ))}
                  </ul>
                  <a 
                    href={plan.link} 
                    className={`btn ${plan.popular ? 'btn-primary' : 'btn-secondary'} btn-block`}
                  >
                    {plan.cta}
                  </a>
                </div>
              ))}
            </div>
          </div>
        </section>

        <section className="pricing-fees">
          <div className="container">
            <h2 className="section-title">Transaction Fees</h2>
            <p className="section-subtitle">
              Transparent pricing for all our services
            </p>
            <div className="fees-table">
              {fees.map((item, index) => (
                <div key={index} className="fee-row">
                  <div className="fee-service">
                    <h4>{item.service}</h4>
                    <p>{item.description}</p>
                  </div>
                  <div className="fee-amount">{item.fee}</div>
                </div>
              ))}
            </div>
          </div>
        </section>

        <section className="pricing-cta">
          <div className="container">
            <div className="cta-box">
              <h2>Ready to Get Started?</h2>
              <p>Create your free account and start accepting payments today</p>
              <a 
                href="https://app.pointwave.ng/auth/register" 
                className="btn btn-primary btn-large"
              >
                Create Free Account
              </a>
            </div>
          </div>
        </section>
      </main>
      <Footer />
    </div>
  );
};

export default PricingPage;
